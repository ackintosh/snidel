<?php
declare(ticks = 1);
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Task\Task;
use Ackintosh\Snidel\Traits\Queueing;

class Worker
{
    use Queueing;

    /** @var \Ackintosh\Snidel\Task\Task */
    private $latestTask;

    /** @var \Ackintosh\Snidel\Fork\Process */
    private $process;

    /** @var \Ackintosh\Snidel\Pcntl */
    private $pcntl;

    /** @var bool */
    private $isInProgress = false;

    /** @var \Bernard\QueueFactory\PersistentFactory */
    private $factory;

    /** @var \Bernard\Producer */
    private $producer;

    /** @var \Bernard\Queue  */
    private $taskQueue;

    /** @var int */
    private $pollingDuration;

    /**
     * @param \Ackintosh\Snidel\Fork\Process $process
     * @param \Bernard\Driver $driver
     * @param int $pollingDuration
     */
    public function __construct($process, $driver, $pollingDuration)
    {
        $this->pcntl = new Pcntl();
        $this->process = $process;

        $this->factory = $this->createFactory($driver);
        $this->producer = $this->createProducer($this->factory);
        $this->taskQueue = $this->factory->create('task');

        $this->pollingDuration = $pollingDuration;
    }

    /**
     * @return  int
     */
    public function getPid()
    {
        return $this->process->getPid();
    }

    /**
     * @return  void
     * @throws  \RuntimeException
     * @codeCoverageIgnore covered by SnidelTest via worker process
     */
    public function run()
    {
        while (true) {
            if ($envelope = $this->taskQueue->dequeue($this->pollingDuration)) {
                $this->task($envelope->getMessage());
            }
        }
    }

    /**
     * @param Task $task
     * @return void
     * @codeCoverageIgnore covered by SnidelTest via worker process
     */
    public function task(Task $task)
    {
        $this->isInProgress = true;
        $this->latestTask = $task;
        $result = $task->execute();
        $result->setProcess($this->process);

        $this->producer->produce($result);
        $this->isInProgress = false;
    }

    /**
     * @return  void
     * @throws  \RuntimeException
     * @codeCoverageIgnore covered by SnidelTest via worker process
     */
    public function error()
    {
        $result = new Result();
        $result->setError(error_get_last());
        $result->setTask($this->latestTask);
        $result->setProcess($this->process);

        try {
            $this->producer->produce($result);
        } catch (\RuntimeException $e) {
            throw $e;
        }
    }

    /**
     * @param   int     $sig
     * @return  void
     * @codeCoverageIgnore covered by SnidelTest via worker process
     */
    public function terminate($sig)
    {
        posix_kill($this->process->getPid(), $sig);
        $status = null;
        $this->pcntl->waitpid($this->process->getPid(), $status);
    }

    /**
     * @return bool
     */
    public function hasTask()
    {
        return $this->latestTask !== null;
    }

    /**
     * @return bool
     */
    public function isInProgress()
    {
        return $this->isInProgress;
    }
}
