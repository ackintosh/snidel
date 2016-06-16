<?php

use Ackintosh\Snidel\Fork\Container;
use Ackintosh\Snidel\Log;
use Ackintosh\Snidel\Task\Task;

/**
 * @runTestsInSeparateProcesses
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function enqueueThrowsExceptionWhenFailed()
    {
        $container = new Container(getmypid(), new Log(getmypid()));
        $task = new Task(
            function ($args) {
                return $args;
            }, 
            'foo',
            null
        );

        require_once(__DIR__ . '/../../msg_send.php');
        $container->enqueue($task);
    }
}
