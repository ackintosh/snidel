<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Fork;
use Ackintosh\Snidel\ForkCollection;
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

    /**
     * @param   int     $ownerPid
     */
    public function __construct($ownerPid)
    {
        $this->ownerPid         = $ownerPid;
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

        $fork = new Fork($pid);
        $fork->setTask($task);

        return $fork;
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
