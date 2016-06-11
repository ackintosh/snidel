<?php
namespace Ackintosh\Snidel\Fork;

use Ackintosh\Snidel\Fork\Fork;
use Ackintosh\Snidel\Pcntl;
use Ackintosh\Snidel\DataRepository;
use Ackintosh\Snidel\Task\Queue as TaskQueue;
use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Result\Queue as ResultQueue;
use Ackintosh\Snidel\Result\Collection;
use Ackintosh\Snidel\Error;
use Ackintosh\Snidel\Exception\SharedMemoryControlException;
use Ackintosh\Snidel\Worker;
use Ackintosh\Snidel\ActiveWorkerSet;

class Container
{
    /** @var int */
    private $ownerPid;

    /** @var int */
    private $masterPid;

    /** @var int[] */
    private $workerPids = array();

    /** @var \Ackintosh\Snidel\Fork\Fork[] */
    private $forks = array();

    /** @var \Ackintosh\Snidel\Result\Result[] */
    private $results = array();

    /** @var \Ackintosh\Snidel\Pcntl */
    private $pcntl;

    /** @var \Ackintosh\Snidel\DataRepository */
    private $dataRepository;

    /** @var \Ackintosh\Snidel\Error */
    private $error;

    /** @var \Ackintosh\Snidel\Task\Queue */
    private $taskQueue;

    /** @var \Ackintosh\Snidel\Result\Queue */
    private $resultQueue;

    /** @var \Ackintosh\Snidel\Log */
    private $log;

    /** @var array */
    private $signals = array(
        SIGTERM,
        SIGINT,
    );

    /** @var int */
    private $concurrency;

    /**
     * @param   int     $ownerPid
     */
    public function __construct($ownerPid, $log, $concurrency = 5)
    {
        $this->ownerPid         = $ownerPid;
        $this->log              = $log;
        $this->concurrency      = $concurrency;
        $this->pcntl            = new Pcntl();
        $this->dataRepository   = new DataRepository();
        $this->taskQueue        = new TaskQueue($this->ownerPid);
        $this->resultQueue      = new ResultQueue($this->ownerPid);
        $this->error            = new Error();
    }

    /**
     * @param   \Ackintosh\Snidel\Task
     * @return  void
     * @throws  \RuntimeException
     */
    public function enqueue($task)
    {
        try {
            $this->taskQueue->enqueue($task);
        } catch (\RuntimeException $e) {
            throw $e;
        }
    }

    /**
     * @return  int
     */
    public function queuedCount()
    {
        return $this->taskQueue->queuedCount();
    }

    /**
     * @return  \Ackintosh\Snidel\Fork\Fork
     */
    private function dequeue()
    {
        return $this->resultQueue->dequeue();
    }

    /**
     * @return  int
     */
    public function dequeuedCount()
    {
        return $this->resultQueue->dequeuedCount();
    }

    /**
     * fork process
     *
     * @return  \Ackintosh\Snidel\Fork\Fork
     * @throws  \RuntimeException
     */
    public function fork()
    {
        $pid = $this->pcntl->fork();
        if ($pid === -1) {
            throw new \RuntimeException('could not fork a new process');
        }

        $pid = ($pid === 0) ? getmypid() : $pid;

        $fork = new Fork($pid);
        $this->forks[$pid] = $fork;

        return $fork;
    }

    /**
     * fork master process
     *
     * @return  int     $masterPid
     */
    public function forkMaster()
    {
        try {
            $fork = $this->fork();
        } catch (\RuntimeException $e) {
            throw $e;
        }

        $this->masterPid = $fork->getPid();
        $this->log->setMasterPid($this->masterPid);

        if (getmypid() === $this->ownerPid) {
            // owner
            $this->log->info('pid: ' . getmypid());

            return $this->masterPid;
        } else {
            // master
            $taskQueue          = new TaskQueue($this->ownerPid);
            $activeWorderSet    = new ActiveWorkerSet();
            $this->log->info('pid: ' . $this->masterPid);

            $log    = $this->log;
            $pcntl  = $this->pcntl;
            foreach ($this->signals as $sig) {
                $this->pcntl->signal($sig, function ($sig) use ($log, $pcntl, $activeWorderSet) {
                    $log->info('received signal: ' . $sig);
                    foreach ($activeWorderSet->toArray() as $worker) {
                        $log->info('------> sending signal to worker. signal: ' . $sig);
                        posix_kill($worker->getPid(), $sig);
                        $log->info('<------ sent signal');
                        $status = null;
                        $pcntl->waitpid($worker->getPid(), $status);
                    }
                    exit;
                });
            }

            while ($task = $taskQueue->dequeue()) {
                $this->log->info('dequeued task #' . $taskQueue->dequeuedCount());
                if ($activeWorderSet->count() >= $this->concurrency) {
                    $status = null;
                    $workerPid = $this->pcntl->waitpid(-1, $status);
                    $activeWorderSet->delete($workerPid);
                }
                $activeWorderSet->add(
                    $this->forkWorker($task)
                );
            }
            exit;
        }
    }

    /**
     * fork worker process
     *
     * @param   \Ackintosh\Snidel\Task
     * @return  \Ackintosh\Snidel\Worker
     * @throws  \RuntimeException
     */
    private function forkWorker($task)
    {
        try {
            $fork = $this->fork();
        } catch (\RuntimeException $e) {
            $this->log->error($e->getMessage());
            throw $e;
        }

        $worker = new Worker($fork, $task);

        if (getmypid() === $this->masterPid) {
            // master
            $this->log->info('forked worker. pid: ' . $worker->getPid());
            return $worker;
        } else {
            // worker
            $this->log->info('has forked. pid: ' . getmypid());
            // @codeCoverageIgnoreStart

            foreach ($this->signals as $sig) {
                $this->pcntl->signal($sig, SIG_DFL, true);
            }

            $worker->setResultQueue(new ResultQueue($this->ownerPid));

            $resultHasQueued = false;
            register_shutdown_function(function () use (&$resultHasQueued, $worker) {
                if (!$resultHasQueued) {
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

            $resultHasQueued = true;
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
        return $this->masterPid !== null;
    }

    /**
     * send signal to master process
     *
     * @return  void
     */
    public function sendSignalToMaster($sig = SIGTERM)
    {
        $this->log->info('----> sending signal to master. signal: ' . $sig);
        posix_kill($this->masterPid, $sig);
        $this->log->info('<---- sent signal.');

        $status = null;
        $this->pcntl->waitpid($this->masterPid, $status);
        $this->log->info('. status: ' . $status);
        $this->masterPid = null;
    }

    /**
     *
     * @param   string  $tag
     * @return  bool
     */
    public function hasTag($tag)
    {
        foreach ($this->results as $result) {
            if ($result->getTask()->getTag() === $tag) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return void
     */
    public function wait()
    {
        for (; $this->queuedCount() > $this->dequeuedCount();) {
            $result = $this->dequeue();
            $pid = $result->getFork()->getPid();
            $this->results[$pid] = $result;

            if ($result->isFailure()) {
                $this->error[$pid] = $result;
            }
        }
    }

    /**
     * wait child
     *
     * @return \Ackintosh\Snidel\Result\Result
     */
    public function waitForChild()
    {
        $status = null;
        $childPid = $this->pcntl->waitpid(-1, $status);
        try {
            $result = $this->dataRepository->load($childPid)->readAndDelete();
        } catch (SharedMemoryControlException $e) {
            throw $e;
        }
        $fork = $result->getFork();
        $fork->setStatus($status);
        $result->setFork($fork);

        if ($result->isFailure() || !$this->pcntl->wifexited($status) || $this->pcntl->wexitstatus($status) !== 0) {
            $this->error[$childPid] = $fork;
        }
        $this->results[$childPid] = $result;

        return $result;
    }

    /**
     * @return  array
     */
    public function getChildPids()
    {
        return array_keys($this->forks);
    }

    /**
     * return fork
     *
     * @param   int     $pid
     * @return  \Ackintosh\Snidel\Fork\Fork
     */
    public function get($pid)
    {
        return $this->results[$pid];
    }

    public function getCollection($tag = null)
    {
        if ($tag === null) {
            $collection = new Collection($this->results);
            $this->results = array();

            return $collection;
        }

        return $this->getCollectionWithTag($tag);
    }

    /**
     * return results
     *
     * @param   string  $tag
     * @return  \Ackintosh\Snidel\Result\Collection
     */
    private function getCollectionWithTag($tag)
    {
        $results = array();
        foreach ($this->results as $r) {
            if ($r->getTask()->getTag() !== $tag) {
                continue;
            }

            $results[] = $r;
            unset($this->results[$r->getFork()->getPid()]);
        }

        return new Collection($results);
    }

    /**
     * @return  bool
     */
    public function hasError()
    {
        return $this->error->exists();
    }

    /**
     * @return  \Ackintosh\Sniden\Error
     */
    public function getError()
    {
        return $this->error;
    }

    public function __destruct()
    {
        unset($this->taskQueue);
        unset($this->resultQueue);
    }
}
