<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Fork\Fork;
use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Result\Queue as ResultQueue;
use Ackintosh\Snidel\Task\Queue as TaskQueue;
use Ackintosh\Snidel\Task\Task;
use Ackintosh\Snidel\Fork\Container;

/**
 * @codeCoverageIgnore
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \Ackintosh\Snidel\Fork\Fork
     */
    protected function makeFork($pid = null)
    {
        return new Fork($pid ? $pid : getmypid());
    }

    /**
     * @return \Ackintosh\Task\Task
     */
    protected function makeTask()
    {
        return new Task('receivesArgumentsAndReturnsIt', 'foo', null);
    }

    /**
     * @return \Ackintosh\Snidel\Result\Result
     */
    protected function makeResult()
    {
        $result = new Result();
        $result->setFork($this->makeFork());
        $result->setTask($this->makeTask());

        return $result;
    }

    /**
     * @return \Ackintosh\Snidel\Result\Queue
     */
    protected function makeResultQueue()
    {
        return new ResultQueue($this->makeDefaultConfig());
    }

    /**
     * @return \Ackintosh\Snidel\Task\Queue
     */
    protected function makeTaskQueue()
    {
        return new TaskQueue($this->makeDefaultConfig());
    }

    /**
     * @return \Ackintosh\Snidel\Fork\Container
     */
    protected function makeForkContainer()
    {
        return \ClassProxy::on(new Container(
            getmypid(),
            new Log(getmypid(), null),
            $this->makeDefaultConfig()
        ));
    }

    /**
     * @return \Ackintosh\Snidel\Worker
     */
    protected function makeWorker($pid = null)
    {
        $pid = $pid ?: getmypid();

        return new Worker(new Fork($pid));
    }

    protected function makeDefaultConfig()
    {
        return new Config(array('concurrency' => 5));
    }

    /**
     * @param mixed $target
     * @param \Ackintosh\Snidel\Semaphore $semaphore
     * @return \Ackintosh\Snidel\AbstractQueue
     */
    protected function setSemaphore($target, $semaphore)
    {
        $prop = new \ReflectionProperty($target, 'semaphore');
        $prop->setAccessible(true);
        $prop->setValue($target, $semaphore);

        return $target;
    }
}
