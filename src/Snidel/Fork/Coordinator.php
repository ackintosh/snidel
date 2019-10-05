<?php
declare(strict_types=1);
declare(ticks=1);
namespace Ackintosh\Snidel\Fork;

use Ackintosh\Snidel\Log;
use Ackintosh\Snidel\Task\Task;
use Ackintosh\Snidel\WorkerPool;
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

    public function __construct(Config $config, Log $log)
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
     * @throws  \RuntimeException
     */
    public function enqueue(Task $task): void
    {
        try {
            $this->producer->produce($task);
            $this->queuedCount++;

        } catch (\RuntimeException $e) {
            throw $e;
        }
    }

    public function queuedCount(): int
    {
        return $this->queuedCount;
    }

    public function dequeuedCount(): int
    {
        return $this->dequeuedCount;
    }

    /**
     * fork master process
     *
     * @throws \RuntimeException
     */
    public function forkMaster(): Process
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
            $workerPool = new WorkerPool();
            $this->log->info('pid: ' . $this->master->getPid());

            foreach ($this->signals as $sig) {
                $this->pcntl->signal($sig, function ($sig) use ($workerPool) {
                    $this->receivedSignal = $sig;
                    $this->log->info('received signal: ' . $sig);

                    if ($workerPool->count() === 0) {
                        $this->log->info('no worker is active.');
                    } else {
                        $this->log->info('------> sending signal to workers. signal: ' . $sig);
                        $workerPool->terminate($sig);
                        $this->log->info('<------ sent signal');
                    }
                    exit;
                });
            }

            $concurrency = (int)$this->config->get('concurrency');
            for ($i = 0; $i < $concurrency; $i++) {
                $workerPool->add($this->forkWorker());
            }
            $status = null;
            while (($workerPid = $this->pcntl->waitpid(-1, $status, WNOHANG)) !== -1) {
                if ($workerPid === true || $workerPid === 0) {
                    usleep(100000);
                    continue;
                }
                $workerPool->delete($workerPid);
                $workerPool->add($this->forkWorker());
                $status = null;
            }
            exit;
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * fork worker process
     *
     * @throws  \RuntimeException
     */
    private function forkWorker(): Worker
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

    public function existsMaster(): bool
    {
        return $this->master !== null;
    }

    /**
     * send signal to master process
     */
    public function sendSignalToMaster($sig = SIGTERM): void
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

    public function wait(): void
    {
        foreach ($this->results() as $_) {}
    }

    public function results(): \Generator
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

    public function hasError(): bool
    {
        return $this->error->exists();
    }

    public function getError(): Error
    {
        return $this->error;
    }

    public function __destruct()
    {
        unset($this->resultQueue);
    }
}
