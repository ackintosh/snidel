<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Fork;
use Ackintosh\Snidel\ForkCollection;
use Ackintosh\Snidel\Token;
use Ackintosh\Snidel\Pcntl;
use Ackintosh\Snidel\DataRepository;
use Ackintosh\Snidel\TaskQueue;
use Ackintosh\Snidel\ResultQueue;
use Ackintosh\Snidel\Error;
use Ackintosh\Snidel\Exception\SharedMemoryControlException;

class ForkContainer
{
    /** @var int */
    private $ownerPid;

    /** @var \Ackintosh\Snidel\Fork[] */
    private $forks = array();

    /** @var \Ackintosh\Snidel\Pcntl */
    private $pcntl;

    /** @var \Ackintosh\Snidel\DataRepository */
    private $dataRepository;

    /** @var \Ackintosh\Snidel\Error */
    private $error;

    /** @var \Ackintosh\Snidel\TaskQueue */
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

    /**
     * @param   int     $ownerPid
     */
    public function __construct($ownerPid, $log, $concurrency = 5)
    {
        $this->ownerPid         = $ownerPid;
        $this->log              = $log;
        $this->token            = new Token($this->ownerPid, $concurrency);
        $this->pcntl            = new Pcntl();
        $this->dataRepository   = new DataRepository();
        $this->taskQueue        = new TaskQueue($this->ownerPid);
        $this->resultQueue      = new ResultQueue($this->ownerPid);
        $this->error            = new Error();
    }

    /**
     * @param   \Ackintosh\Snidel\Task
     * @return  void
     */
    public function enqueue($task)
    {
        $this->taskQueue->enqueue($task);
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
     * @return  int     $masterProcessId
     */
    public function forkMaster()
    {
        $pid = $this->pcntl->fork();
        $this->masterProcessId = ($pid === 0) ? getmypid() : $pid;
        $this->log->setMasterProcessId($this->masterProcessId);

        if ($pid) {
            // owner
            $this->log->info('pid: ' . getmypid());

            return $this->masterProcessId;
        } elseif ($pid === -1) {
            // error
        } else {
            // master
            $taskQueue = new TaskQueue($this->ownerPid);
            $this->log->info('pid: ' . $this->masterProcessId);

            foreach ($this->signals as $sig) {
                $this->pcntl->signal($sig, SIG_DFL, true);
            }

            while ($task = $taskQueue->dequeue()) {
                $this->log->info('dequeued task #' . $taskQueue->dequeuedCount());
                if ($this->token->accept()) {
                    $this->forkWorker($task);
                }
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

        if (getmypid() === $this->masterProcessId) {
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
            register_shutdown_function(function () use ($fork, $resultQueue) {
                if ($fork->hasNoResult() || !$fork->isQueued()) {
                    $result = new Result();
                    $result->setFailure();
                    $fork->setResult($result);
                    $resultQueue->enqueue($fork);
                }
            });

            $this->log->info('----> started the function.');
            $fork->executeTask();
            $this->log->info('<---- completed the function.');

            $resultQueue->enqueue($fork);
            $fork->setQueued();
            $this->log->info('queued the result.');

            $this->token->back();
            $this->log->info('return the token and exit.');
            exit;
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     *
     * @param   string  $tag
     * @return  bool
     */
    public function hasTag($tag)
    {
        foreach ($this->forks as $fork) {
            if ($fork->getTag() === $tag) {
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
            $fork = $this->dequeue();
            $this->forks[$fork->getPid()] = $fork;

            if ($fork->getResult()->isFailure()) {
                $this->error[$fork->getPid()] = $fork;
            }
        }
    }

    /**
     * wait child
     *
     * @return \Ackintosh\Snidel\Fork
     */
    public function waitSimply()
    {
        $status = null;
        $childPid = $this->pcntl->waitpid(-1, $status);
        try {
            $fork = $this->dataRepository->load($childPid)->readAndDelete();
        } catch (SharedMemoryControlException $e) {
            throw $e;
        }
        $fork->setStatus($status);

        if ($fork->hasNotFinishedSuccessfully()) {
            $this->error[$childPid] = $fork;
        }

        $this->forks[$childPid] = $fork;
        return $fork;
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
        return $this->forks[$pid];
    }

    public function getCollection($tag = null)
    {
        if ($tag === null) {
            return new ForkCollection($this->forks);
        }

        return $this->getCollectionWithTag($tag);
    }

    /**
     * return forks
     *
     * @param   string  $tag
     * @return  \Ackintosh\Snidel\Fork[]
     */
    private function getCollectionWithTag($tag)
    {
        $collection = array_filter($this->forks, function ($fork) use ($tag) {
            return $fork->getTag() ===  $tag;
        });

        return new ForkCollection($collection);
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
