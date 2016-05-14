<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Fork;
use Ackintosh\Snidel\ForkCollection;
use Ackintosh\Snidel\Pcntl;
use Ackintosh\Snidel\DataRepository;
use Ackintosh\Snidel\Task\Queue as TaskQueue;
use Ackintosh\Snidel\ResultQueue;
use Ackintosh\Snidel\ResultCollection;
use Ackintosh\Snidel\Error;
use Ackintosh\Snidel\Exception\SharedMemoryControlException;

class ForkContainer
{
    /** @var int */
    private $ownerPid;

    /** @var int */
    private $masterPid;

    /** @var \Ackintosh\Snidel\Fork[] */
    private $forks = array();

    /** @var \Ackintosh\Snidel\Result[] */
    private $results = array();

    /** @var \Ackintosh\Snidel\Pcntl */
    private $pcntl;

    /** @var \Ackintosh\Snidel\DataRepository */
    private $dataRepository;

    /** @var \Ackintosh\Snidel\Error */
    private $error;

    /** @var \Ackintosh\Snidel\Task\Queue */
    private $taskQueue;

    /** @var \Ackintosh\Snidel\ResultQueue */
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
     * @return  \Ackintosh\Snidel\Fork
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
     * @param   \Ackintosh\Snidel\Task
     * @return  \Ackintosh\Snidel\Fork
     * @throws  \RuntimeException
     */
    public function fork($task)
    {
        $pid = $this->pcntl->fork();
        if ($pid === -1) {
            throw new \RuntimeException('could not fork a new process');
        }

        $pid = ($pid === 0) ? getmypid() : $pid;

        $fork = new Fork($pid, $task);
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
        $pid = $this->pcntl->fork();
        $this->masterPid = ($pid === 0) ? getmypid() : $pid;
        $this->log->setMasterPid($this->masterPid);

        if ($pid) {
            // owner
            $this->log->info('pid: ' . getmypid());

            return $this->masterPid;
        } elseif ($pid === -1) {
            // error
        } else {
            // master
            $taskQueue = new TaskQueue($this->ownerPid);
            $this->log->info('pid: ' . $this->masterPid);

            foreach ($this->signals as $sig) {
                $this->pcntl->signal($sig, SIG_DFL, true);
            }
            $workerCount = 0;

            while ($task = $taskQueue->dequeue()) {
                $this->log->info('dequeued task #' . $taskQueue->dequeuedCount());
                if ($workerCount >= $this->concurrency) {
                    $status = null;
                    $this->pcntl->waitpid(-1, $status);
                    $workerCount--;
                }
                $this->forkWorker($task);
                $workerCount++;
            }
            exit;
        }
    }

    /**
     * fork worker process
     *
     * @param   \Ackintosh\Snidel\Task
     * @return  void
     * @throws  \RuntimeException
     */
    private function forkWorker($task)
    {
        try {
            $fork = $this->fork($task);
        } catch (\RuntimeException $e) {
            $this->log->error($e->getMessage());
            throw $e;
        }

        if (getmypid() === $this->masterPid) {
            // master
            $this->log->info('forked worker. pid: ' . $fork->getPid());
        } else {
            // worker
            $this->log->info('has forked. pid: ' . getmypid());
            // @codeCoverageIgnoreStart

            foreach ($this->signals as $sig) {
                $this->pcntl->signal($sig, SIG_DFL, true);
            }

            $resultQueue = new ResultQueue($this->ownerPid);
            $resultHasQueued = false;
            register_shutdown_function(function () use (&$resultHasQueued, $fork, $resultQueue) {
                if (!$resultHasQueued) {
                    $result = new Result();
                    $result->setFailure();
                    $result->setFork($fork);
                    $resultQueue->enqueue($result);
                }
            });

            $this->log->info('----> started the function.');
            $result = $fork->executeTask();
            $this->log->info('<---- completed the function.');

            try {
                $resultQueue->enqueue($result);
            } catch (\RuntimeException $e) {
                $this->log->error($e->getMessage());
                $result->setFailure();
                $resultQueue->enqueue($result);
            }
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
     * kill master process
     *
     * @return  void
     */
    public function killMaster()
    {
        posix_kill($this->masterPid, SIGTERM);
    }

    /**
     *
     * @param   string  $tag
     * @return  bool
     */
    public function hasTag($tag)
    {
        foreach ($this->results as $result) {
            if ($result->getFork()->getTag() === $tag) {
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
     * @return \Ackintosh\Snidel\Result
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

        if ($fork->hasNotFinishedSuccessfully()) {
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
     * @return  \Ackintosh\Snidel\Fork
     */
    public function get($pid)
    {
        return $this->results[$pid];
    }

    public function getCollection($tag = null)
    {
        if ($tag === null) {
            $collection = new ResultCollection($this->results);
            $this->results = array();

            return $collection;
        }

        return $this->getCollectionWithTag($tag);
    }

    /**
     * return results
     *
     * @param   string  $tag
     * @return  \Ackintosh\Snidel\ResultCollection
     */
    private function getCollectionWithTag($tag)
    {
        $results = array();
        foreach ($this->results as $r) {
            if ($r->getFork()->getTag() !== $tag) {
                continue;
            }

            $results[] = $r;
            unset($this->results[$r->getFork()->getPid()]);
        }

        return new ResultCollection($results);
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
