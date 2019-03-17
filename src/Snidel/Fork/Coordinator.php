<?php
declare(ticks=1);
namespace Ackintosh\Snidel\Fork;

use Ackintosh\Snidel\ActiveWorkerSet;
use Ackintosh\Snidel\Config;
use Ackintosh\Snidel\Error;
use Ackintosh\Snidel\Pcntl;
use Ackintosh\Snidel\Traits\Queueing;
use Ackintosh\Snidel\Worker;
use Bernard\Router\SimpleRouter;

class Coordinator
{
    use Queueing;

    /** @var Process */
    private $master;

    /** @var \Ackintosh\Snidel\Pcntl */
    private $pcntl;

    /** @var \Ackintosh\Snidel\Error */
    private $error;

    /** @var \Ackintosh\Snidel\Log */
    private $log;

    /** @var array */
    private $signals = [
        SIGTERM,
        SIGINT,
    ];

    /** @var \Ackintosh\Snidel\Config */
    private $config;

    /** @var  int */
    private $receivedSignal;

    /** @var int */
    private $queuedCount = 0;
    /** @var int */
    private $dequeuedCount = 0;

    /** @var \Bernard\QueueFactory\PersistentFactory */
    private $factory;

    /** @var \Bernard\Producer */
    private $producer;

    /** @var \Bernard\Consumer */
    private $consumer;

    /** @var \Bernard\Queue  */
    private $resultQueue;

    /**
     * @param   int     $ownerPid
     */
    public function __construct(Config $config, $log)
    {
        $this->log = $log;
        $this->config = $config;
        $this->pcntl = new Pcntl();
        $this->error = new Error();

        $this->factory = $this->createFactory($this->config->get('driver'));
        $router = new SimpleRouter();
        $router->add('Result', $this);
        $this->consumer = $this->createConsumer($router);
        $this->producer = $this->createProducer($this->factory);
    }

    /**
     * @param   \Ackintosh\Snidel\Task
     * @return  void
     * @throws  \RuntimeException
     */
    public function enqueue($task)
    {
        try {
            $this->producer->produce($task);
            $this->queuedCount++;

        } catch (\RuntimeException $e) {
            throw $e;
        }
    }

    /**
     * @return  int
     */
    public function queuedCount()
    {
        return $this->queuedCount;
    }

    /**
     * @return  int
     */
    public function dequeuedCount()
    {
        return $this->dequeuedCount;
    }

    /**
     * fork master process
     *
     * @return Process $master
     * @throws \RuntimeException
     */
    public function forkMaster()
    {
        try {
            $this->master = $this->pcntl->fork();
        } catch (\RuntimeException $e) {
            $message = 'failed to fork master: ' . $e->getMessage();
            $this->log->error($message);
            throw new \RuntimeException($message);
        }

        $this->log->setMasterPid($this->master->getPid());

        if (getmypid() === $this->config->get('ownerPid')) {
            // owner
            $this->log->info('pid: ' . getmypid());
            $this->resultQueue  = $this->factory->create('result');

            return $this->master;
        } else {
            // @codeCoverageIgnoreStart
            // covered by SnidelTest via master process
            // master
            $activeWorkerSet = new ActiveWorkerSet();
            $this->log->info('pid: ' . $this->master->getPid());

            foreach ($this->signals as $sig) {
                $this->pcntl->signal($sig, function ($sig) use ($activeWorkerSet) {
                    $this->receivedSignal = $sig;
                    $this->log->info('received signal: ' . $sig);

                    if ($activeWorkerSet->count() === 0) {
                        $this->log->info('no worker is active.');
                    } else {
                        $this->log->info('------> sending signal to workers. signal: ' . $sig);
                        $activeWorkerSet->terminate($sig);
                        $this->log->info('<------ sent signal');
                    }
                    exit;
                });
            }

            $concurrency = (int)$this->config->get('concurrency');
            for ($i = 0; $i < $concurrency; $i++) {
                $activeWorkerSet->add($this->forkWorker());
            }
            $status = null;
            while (($workerPid = $this->pcntl->waitpid(-1, $status, WNOHANG)) !== -1) {
                if ($workerPid === true || $workerPid === 0) {
                    usleep(100000);
                    continue;
                }
                $activeWorkerSet->delete($workerPid);
                $activeWorkerSet->add($this->forkWorker());
                $status = null;
            }
            exit;
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * fork worker process
     *
     * @return  \Ackintosh\Snidel\Worker
     * @throws  \RuntimeException
     */
    private function forkWorker()
    {
        try {
            $process = $this->pcntl->fork();
        } catch (\RuntimeException $e) {
            $message = 'failed to fork worker: ' . $e->getMessage();
            $this->log->error($message);
            throw new \RuntimeException($message);
        }

        $worker = new Worker($process, $this->config->get('driver'), $this->config->get('pollingDuration'));

        if (getmypid() === $this->master->getPid()) {
            // master
            $this->log->info('forked worker. pid: ' . $worker->getPid());
            return $worker;
        } else {
            // @codeCoverageIgnoreStart
            // covered by SnidelTest via worker process
            // worker
            $this->log->info('has forked. pid: ' . getmypid());

            foreach ($this->signals as $sig) {
                $this->pcntl->signal($sig, function ($sig) {
                    $this->receivedSignal = $sig;
                    exit;
                }, false);
            }

            register_shutdown_function(function () use ($worker) {
                if ($this->receivedSignal === null && $worker->isInProgress()) {
                    $worker->error();
                }
            });

            $this->log->info('----> started the function.');
            try {
                $worker->run();
            } catch (\RuntimeException $e) {
                $this->log->error($e->getMessage());
                exit;
            }
            $this->log->info('<---- completed the function.');

            $this->log->info('queued the result and exit.');
            exit;
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @return  bool
     */
    public function existsMaster()
    {
        return $this->master !== null;
    }

    /**
     * send signal to master process
     *
     * @return  void
     */
    public function sendSignalToMaster($sig = SIGTERM)
    {
        $this->log->info('----> sending signal to master. signal: ' . $sig);
        posix_kill($this->master->getPid(), $sig);
        $this->log->info('<---- sent signal.');

        $this->log->info('----> waiting for master shutdown.');
        $status = null;
        $this->pcntl->waitpid($this->master->getPid(), $status);
        $this->log->info('<---- master shutdown. status: ' . $status);
        $this->master = null;
    }

    /**
     * @return void
     */
    public function wait()
    {
        foreach ($this->results() as $_) {}
    }

    /**
     * @return \Generator
     */
    public function results()
    {
        for (; $this->queuedCount() > $this->dequeuedCount();) {
            for (;;) {
                if ($envelope = $this->resultQueue->dequeue($this->config->get('pollingDuration'))) {
                    $this->dequeuedCount++;
                    break;
                }
            }
            $result = $envelope->getMessage();
            if ($result->isFailure()) {
                $pid = $result->getProcess()->getPid();
                $this->error[$pid] = $result;
            } else {
                yield $result;
            }
        }
    }

    /**
     * @return  bool
     */
    public function hasError()
    {
        return $this->error->exists();
    }

    /**
     * @return  \Ackintosh\Snidel\Error
     */
    public function getError()
    {
        return $this->error;
    }

    public function __destruct()
    {
        unset($this->resultQueue);
    }
}
