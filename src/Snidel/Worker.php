<?php
declare(strict_types=1);
declare(ticks=1);

namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Fork\Process;
use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Task\Task;
use Ackintosh\Snidel\Traits\Queueing;
use Bernard\Driver;
use Bernard\QueueFactory\PersistentFactory;
use RuntimeException;

class Worker
{
    use Queueing;

    /** @var Task */
    private $latestTask;

    /** @var Process */
    private $process;

    /** @var Pcntl */
    private $pcntl;

    /** @var bool */
    private $isInProgress = false;

    /** @var PersistentFactory */
    private $factory;

    /** @var \Bernard\Producer */
    private $producer;

    /** @var \Bernard\Queue  */
    private $taskQueue;

    /** @var int */
    private $pollingDuration;

    public function __construct(Process $process, Driver $driver, int $pollingDuration)
    {
        $this->pcntl = new Pcntl();
        $this->process = $process;

        $this->factory = $this->createFactory($driver);
        $this->producer = $this->createProducer($this->factory);

        /*
         * Flat-file driver may causes E_WARNING (mkdir(): File exists) in race condition.
         * Suppress the warning as it isn't matter and we should progress this task.
         */
        if ($driver instanceof \Bernard\Driver\FlatFileDriver) {
            $this->taskQueue = @$this->factory->create('task');
        } else {
            $this->taskQueue = $this->factory->create('task');
        }

        $this->pollingDuration = $pollingDuration;
    }

    public function getPid(): int
    {
        return $this->process->getPid();
    }

    /**
     * @throws  RuntimeException
     * @codeCoverageIgnore covered by SnidelTest via worker process
     */
    public function run(): void
    {
        while (true) {
            if ($envelope = $this->taskQueue->dequeue($this->pollingDuration)) {
                $this->task($envelope->getMessage());
            }
            // We need to insert some statements here as condition expressions are not tickable.
            // Worker process can't receive signals sent from Master if there's no statements here.
            // @see http://jp2.php.net/manual/en/control-structures.declare.php#control-structures.declare.ticks
            usleep(1);
        }
    }

    /**
     * @codeCoverageIgnore covered by SnidelTest via worker process
     */
    public function task(Task $task): void
    {
        $this->isInProgress = true;
        $this->latestTask = $task;
        $result = $task->execute();
        $result->setProcess($this->process);

        $this->producer->produce($result);
        $this->isInProgress = false;
    }

    /**
     * @throws  RuntimeException
     * @codeCoverageIgnore covered by SnidelTest via worker process
     */
    public function error(): void
    {
        $result = new Result();
        $result->setError(error_get_last());
        $result->setTask($this->latestTask);
        $result->setProcess($this->process);

        try {
            $this->producer->produce($result);
        } catch (RuntimeException $e) {
            throw $e;
        }
    }

    /**
     * @codeCoverageIgnore covered by SnidelTest via worker process
     */
    public function terminate(int $sig): void
    {
        posix_kill($this->process->getPid(), $sig);
        $status = null;
        $this->pcntl->waitpid($this->process->getPid(), $status);
    }

    public function hasTask(): bool
    {
        return $this->latestTask !== null;
    }

    public function isInProgress(): bool
    {
        return $this->isInProgress;
    }
}
