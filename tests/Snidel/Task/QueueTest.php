<?php
use Ackintosh\Snidel\Task\Task;
use Ackintosh\Snidel\TestCase;

class TaskQueueTest extends TestCase
{
    /** @var \Ackintosh\Snidel\Task\Queue */
    private $queue;

    public function setUp()
    {
        parent::setUp();
        $this->queue = $this->makeTaskQueue();
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->queue->delete();
    }

    /**
     * @test
     */
    public function enqueue()
    {
        $property   = new \ReflectionProperty('\Ackintosh\Snidel\Task\Queue', 'queuedCount');
        $property->setAccessible(true);

        $this->assertSame(0, $property->getValue($this->queue));

        $this->queue->enqueue(new Task('receivesArgumentsAndReturnsIt', 'foo', null));

        $this->assertSame(1, $property->getValue($this->queue));
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function enqueueThrowsExceptionWhenTaskExceedsTheMessageQueueLimit()
    {
        $property   = new \ReflectionProperty('\Ackintosh\Snidel\Task\Queue', 'stat');
        $property->setAccessible(true);
        $stat   = $property->getValue($this->queue);
        $arg    = str_repeat('a', $stat['msg_qbytes']);

        $this->queue->enqueue(new Task('receivesArgumentsAndReturnsIt', $arg, null));
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function enqueueThrowsExceptionWhenFailedToSendMessage()
    {
        $semaphore = $this->getMockBuilder('\Ackintosh\Snidel\Semaphore')
            ->setMethods(['sendMessage'])
            ->getMock();
        $semaphore->expects($this->once())
            ->method('sendMessage')
            ->willReturn(false);

        $queue = $this->setSemaphore($this->queue, $semaphore);
        $queue->enqueue(new Task('receivesArgumentsAndReturnsIt', ['foo'], null));
    }

    /**
     * @test
     */
    public function dequeue()
    {
        $property   = new \ReflectionProperty('\Ackintosh\Snidel\Task\Queue', 'dequeuedCount');
        $property->setAccessible(true);

        $this->assertSame(0, $property->getValue($this->queue));

        $this->queue->enqueue(new Task('receivesArgumentsAndReturnsIt', 'foo', null));
        $this->queue->dequeue();

        $this->assertSame(1, $property->getValue($this->queue));
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function dequeueThrowsException()
    {
        $this->queue->enqueue(new Task('receivesArgumentsAndReturnsIt', 'foo', null));

        $semaphore = $this->getMockBuilder('\Ackintosh\Snidel\Semaphore')
            ->setMethods(['receiveMessage'])
            ->getMock();
        $semaphore->expects($this->once())
            ->method('receiveMessage')
            ->willReturn(false);

        $queue = $this->setSemaphore($this->queue, $semaphore);
        $queue->dequeue();
    }
}
