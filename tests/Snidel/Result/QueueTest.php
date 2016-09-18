<?php
use Ackintosh\Snidel\Result\Queue;
use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Fork\Fork;
use Ackintosh\Snidel\Task\Task;
use Ackintosh\Snidel\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ResultQueueTest extends TestCase
{
    /** @var Ackintosh\Snidel\Result\Queue */
    private $queue;

    public function setUp()
    {
        $this->queue = $this->makeResultQueue();
    }

    /**
     * @test
     */
    public function enqueue()
    {
        $result = new Result();
        $result->setFork(new Fork(getmypid()));
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
        $result->setFork(new Fork(getmypid()));
        $result->setTask(new Task('receivesArgumentsAndReturnsIt', 'foo', null));

        $this->queue->enqueue($result);
    }

    /**
     * @test
     */
    public function dequeue()
    {
        $result = new Result();
        $result->setFork(new Fork(getmypid()));
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
        $result->setFork(new Fork(getmypid()));
        $result->setTask(new Task('receivesArgumentsAndReturnsIt', 'foo', null));
        $this->queue->enqueue($result);

        require_once(__DIR__ . '/../../msg_receive.php');
        $this->queue->dequeue();
    }
}
