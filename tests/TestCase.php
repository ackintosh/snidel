<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Fork\Process;
use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Result\Queue as ResultQueue;
use Ackintosh\Snidel\Task\Queue as TaskQueue;
use Ackintosh\Snidel\Task\Task;
use Ackintosh\Snidel\Fork\Coordinator;

/**
 * @codeCoverageIgnore
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \Ackintosh\Snidel\Fork\Process
     */
    protected function makeProcess($pid = null)
    {
        return new Process($pid ? $pid : getmypid());
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
        $result->setProcess($this->makeProcess());
        $result->setTask($this->makeTask());

        return $result;
    }

    /**
     * @return \Ackintosh\Snidel\Fork\Coordinator
     */
    protected function makeForkCoordinator()
    {
        return \ClassProxy::on(new Coordinator(
            new Config(),
            new Log(getmypid(), null)
        ));
    }

    /**
     * @return \Ackintosh\Snidel\Worker
     */
    protected function makeWorker($pid = null)
    {
        $pid = $pid ?: getmypid();

        return new Worker(
            new Process($pid),
            (new Config())->get('driver'),
            (new Config())->get('pollingDuration')
        );
    }
}
