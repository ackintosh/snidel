<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Result\QueueInterface as ResultQueueInterface;
use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Result\Formatter as ResultFormatter;
use Ackintosh\Snidel\Task\Formatter as TaskFormatter;
use Ackintosh\Snidel\Task\QueueInterface as TaskQueueInterface;
use Bernard\Consumer;
use Bernard\Driver\FlatFileDriver;
use Bernard\Message\PlainMessage;
use Bernard\Producer;
use Bernard\QueueFactory\PersistentFactory;
use Bernard\Router\SimpleRouter;
use Bernard\Serializer;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Worker
{
    /** @var \Ackintosh\Snidel\Task\Task */
    private $latestTask;

    /** @var \Ackintosh\Snidel\Fork\Process */
    private $process;

    /** @var \Ackintosh\Snidel\Task\QueueInterface */
    private $taskQueue;

    /** @var \Ackintosh\Snidel\Result\QueueInterface */
    private $resultQueue;

    /** @var \Ackintosh\Snidel\Pcntl */
    private $pcntl;

    /** @var bool */
    private $isInProgress = false;

    private $factory;
    private $consumer;
    private $producer;

    /**
     * @param   \Ackintosh\Snidel\Fork\Process $process
     */
    public function __construct($process)
    {
        $this->pcntl = new Pcntl();
        $this->process = $process;

        $driver = new FlatFileDriver('/tmp/hoge');
        $this->factory = new PersistentFactory($driver, new Serializer());
        $router = new SimpleRouter();
        $router->add('Task', $this);
        $this->consumer = new Consumer($router, new EventDispatcher());
        $this->producer = new Producer($this->factory, new EventDispatcher());
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
        return $this->process->getPid();
    }

    /**
     * @return  void
     * @throws  \RuntimeException
     */
    public function run()
    {
        $this->consumer->consume($this->factory->create('task'));
    }

    public function task($message)
    {
        $this->isInProgress = true;
        $this->latestTask = $task = TaskFormatter::unserialize($message['task']);
        $result = $task->execute();
        $result->setProcess($this->process);

        $this->producer->produce(
            new PlainMessage(
                'Result',
                [
                    'result' => ResultFormatter::serialize($result),
                ]
            )
        );
        $this->isInProgress = false;
    }

    /**
     * @return  void
     * @throws  \RuntimeException
     */
    public function error()
    {
        $result = new Result();
        $result->setError(error_get_last());
        $result->setTask($this->latestTask);
        $result->setProcess($this->process);

        try {
            $this->producer->produce(
                new PlainMessage(
                    'Result',
                    [
                        'result' => ResultFormatter::serialize($result),
                    ]
                )
            );
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
