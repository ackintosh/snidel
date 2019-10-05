<?php
use Ackintosh\Snidel\Task\Task;

class TaskTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function execute()
    {
        $task = new Task(
            function ($arg) {
                return 'foo' . $arg;
            },
            ['bar'],
            null
        );

        $this->assertInstanceOf('\Ackintosh\Snidel\Result\Result', $task->execute());
    }
}
