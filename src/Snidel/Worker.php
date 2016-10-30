<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Pcntl;
use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Result\QueueInterface as ResultQueueInterface;
use Ackintosh\Snidel\Task\QueueInterface as TaskQueueInterface;

class Worker
{
    /** @var \Ackintosh\Snidel\Task\Task */
    private $task;

    /** @var \Ackintosh\Snidel\Fork\Fork */
    private $fork;

    /** @var \Ackintosh\Snidel\Task\QueueInterface */
    private $taskQueue;

    /** @var \Ackintosh\Snidel\Result\QueueInterface */
    private $resultQueue;

    /** @var \Ackintosh\Snidel\Pcntl */
    private $pcntl;

    /**
     * @param   \Ackintosh\Snidel\Fork\Fork $fork
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
            $result = $this->taskQueue->dequeue()->execute();
        } catch (\RuntimeException $e) {
            throw $e;
        }

        $result->setFork($this->fork);

        try {
            $this->resultQueue->enqueue($result);
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
        $result->setTask($this->task);
        $result->setFork($this->fork);

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
}
