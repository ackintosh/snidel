<?php
use Ackintosh\Snidel\Task\Queue;
use Ackintosh\Snidel\Task\Task;

/**
 * @runTestsInSeparateProcesses
 */
class TaskQueueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function enqueue()
    {
        $queue      = new Queue(getmypid());
        $property   = new \ReflectionProperty('\Ackintosh\Snidel\Task\Queue', 'queuedCount');
        $property->setAccessible(true);

        $this->assertSame(0, $property->getValue($queue));

        $queue->enqueue(new Task('receivesArgumentsAndReturnsIt', 'foo', null));

        $this->assertSame(1, $property->getValue($queue));
    }

    /**
     * @test
     */
    public function dequeue()
    {
        $queue      = new Queue(getmypid());
        $property   = new \ReflectionProperty('\Ackintosh\Snidel\Task\Queue', 'dequeuedCount');
        $property->setAccessible(true);

        $this->assertSame(0, $property->getValue($queue));

        $queue->enqueue(new Task('receivesArgumentsAndReturnsIt', 'foo', null));
        $queue->dequeue();

        $this->assertSame(1, $property->getValue($queue));
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function dequeueThrowsException()
    {
        $queue = new Queue(getmypid());
        $queue->enqueue(new Task('receivesArgumentsAndReturnsIt', 'foo', null));

        require_once(__DIR__ . '/../../msg_receive.php');
        $queue->dequeue();
    }
}
