<?php
use Ackintosh\Snidel\Result\Queue;
use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Fork\Process;
use Ackintosh\Snidel\Task\Task;
use Ackintosh\Snidel\TestCase;

class ResultQueueTest extends TestCase
{
    /** @var Ackintosh\Snidel\Result\Queue */
    private $queue;

    public function setUp()
    {
        parent::setUp();
        $this->queue = $this->makeResultQueue();
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
        $result = new Result();
        $result->setProcess(new Process(getmypid()));
        $result->setTask(new Task('receivesArgumentsAndReturnsIt', 'foo', null));
        $result = $this->queue->enqueue($result);

        $this->assertTrue($result);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function enqueueThrowsException()
    {
        $property = new \ReflectionProperty('\Ackintosh\Snidel\Result\Queue', 'stat');
        $property->setAccessible(true);
        $stat   = $property->getValue($this->queue);
        $stat['msg_qbytes'] = 1;
        $property->setValue($this->queue, $stat);

        $result = new Result();
        $result->setProcess(new Process(getmypid()));
        $result->setTask(new Task('receivesArgumentsAndReturnsIt', 'foo', null));

        $this->queue->enqueue($result);
    }

    /**
     * @test
     */
    public function dequeue()
    {
        $result = new Result();
        $result->setProcess(new Process(getmypid()));
        $result->setTask(new Task('receivesArgumentsAndReturnsIt', 'foo', null));
        $this->queue->enqueue($result);

        $dequeued = $this->queue->dequeue();
        $this->assertInstanceOf('\Ackintosh\Snidel\Result\Result', $dequeued);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function dequeueThrowsException()
    {
        $result = new Result();
        $result->setProcess(new Process(getmypid()));
        $result->setTask(new Task('receivesArgumentsAndReturnsIt', 'foo', null));
        $this->queue->enqueue($result);

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
