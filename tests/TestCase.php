<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Config;
use Ackintosh\Snidel\Log;
use Ackintosh\Snidel\Worker;
use Ackintosh\Snidel\Fork\Fork;
use Ackintosh\Snidel\Result\Result;
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
    protected function makeFork()
    {
        return new Fork(getmypid());
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
     * @return \Ackintosh\Snidel\Fork\Container
     */
    protected function makeForkContainer()
    {
        return new Container(
            getmypid(),
            new Log(getmypid()),
            $this->makeDefaultConfig()
        );
    }

    /**
     * @return \Ackintosh\Snidel\Worker
     */
    protected function makeWorker($pid = null)
    {
        $pid = $pid ?: getmypid();

        return new Worker(
            new Fork($pid),
            $this->makeTask()
        );
    }

    protected function makeDefaultConfig()
    {
        return new Config(array('concurrency' => 5));
    }
}
