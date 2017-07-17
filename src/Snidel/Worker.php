<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Result\QueueInterface as ResultQueueInterface;
use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Task\QueueInterface as TaskQueueInterface;
use Ackintosh\Snidel\Task\Task;

class Worker
{
    /** @var \Ackintosh\Snidel\Task\Task */
    private $task;

    /** @var \Ackintosh\Snidel\Fork\Process */
    private $fork;

    /** @var \Ackintosh\Snidel\Task\QueueInterface */
    private $taskQueue;

    /** @var \Ackintosh\Snidel\Result\QueueInterface */
    private $resultQueue;

    /** @var \Ackintosh\Snidel\Pcntl */
    private $pcntl;

    /** @var bool */
    private $isReceivedTask = false;

    /** @var bool */
    private $isEnqueuedResult = false;

    /**
     * @param   \Ackintosh\Snidel\Fork\Process $fork
     */
    public function __construct($fork)
    {
        $this->pcntl    = new Pcntl();
        $this->fork     = $fork;
    }

    /**
     * @param   \Ackintosh\Snidel\Task\QueueInterface
     * @return  void
     */
    public function setTaskQueue(TaskQueueInterface $queue)
    {
        $this->taskQueue = $queue;
    }

    /**
     * @param   \Ackintosh\Snidel\Result\QueueInterface
     * @return  void
     */
    public function setResultQueue(ResultQueueInterface $queue)
    {
        $this->resultQueue = $queue;
    }

    /**
     * @return  int
     */
    public function getPid()
    {
        return $this->fork->getPid();
    }

    /**
     * @return  void
     * @throws  \RuntimeException
     */
    public function run()
    {
        try {
            $task = $this->taskQueue->dequeue();
            $this->isReceivedTask = true;
            $result = $task->execute();
        } catch (\RuntimeException $e) {
            throw $e;
        }

        $result->setProcess($this->fork);

        try {
            $this->resultQueue->enqueue($result);
            $this->isEnqueuedResult = true;
        } catch (\RuntimeException $e) {
            throw $e;
        }
    }

    /**
     * @return  void
     * @throws  \RuntimeException
     */
    public function error()
    {
        $result = new Result();
        $result->setError(error_get_last());
        $result->setTask(new Task('echo', [], null));
        $result->setProcess($this->fork);

        try {
            $this->resultQueue->enqueue($result);
        } catch (\RuntimeException $e) {
            throw $e;
        }
    }

    /**
     * @param   int     $sig
     * @return  void
     */
    public function terminate($sig)
    {
        posix_kill($this->fork->getPid(), $sig);
        $status = null;
        $this->pcntl->waitpid($this->fork->getPid(), $status);
    }

    /**
     * @return bool
     */
    public function isFailedToEnqueueResult()
    {
        return $this->isReceivedTask && !$this->isEnqueuedResult;
    }
}
