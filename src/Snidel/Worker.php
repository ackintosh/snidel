<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Result\Formatter as ResultFormatter;
use Ackintosh\Snidel\Task\Normalizer as TaskNormalizer;
use Ackintosh\Snidel\Task\Task;
use Bernard\Consumer;
use Bernard\Message\PlainMessage;
use Bernard\Normalizer\EnvelopeNormalizer;
use Bernard\Normalizer\PlainMessageNormalizer;
use Bernard\Producer;
use Bernard\QueueFactory\PersistentFactory;
use Bernard\Router\SimpleRouter;
use Bernard\Serializer;
use Normalt\Normalizer\AggregateNormalizer;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Worker
{
    /** @var \Ackintosh\Snidel\Task\Task */
    private $latestTask;

    /** @var \Ackintosh\Snidel\Fork\Process */
    private $process;

    /** @var \Ackintosh\Snidel\Pcntl */
    private $pcntl;

    /** @var bool */
    private $isInProgress = false;

    private $factory;
    private $consumer;
    private $producer;

    /**
     * @param \Ackintosh\Snidel\Fork\Process $process
     * @param \Bernard\Driver $driver
     */
    public function __construct($process, $driver)
    {
        $this->pcntl = new Pcntl();
        $this->process = $process;

        $aggregateNormalizer = new AggregateNormalizer([
            new EnvelopeNormalizer(),
            new PlainMessageNormalizer(),
            new TaskNormalizer()
        ]);
        $this->factory = new PersistentFactory($driver, new Serializer($aggregateNormalizer));
        $router = new SimpleRouter();
        $router->add('Task', $this);
        $this->consumer = new Consumer($router, new EventDispatcher());
        $this->producer = new Producer($this->factory, new EventDispatcher());
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
        $this->consumer->consume($this->factory->create('task'));
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
     * @codeCoverageIgnore covered by SnidelTest via worker process
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
