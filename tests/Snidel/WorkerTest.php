<?php
use Ackintosh\Snidel\Worker;
use Ackintosh\Snidel\Result\Queue;
use Ackintosh\Snidel\Fork\Fork;
use Ackintosh\Snidel\Task\Task;

class WorkerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->worker = new Worker(
            new Fork(getmypid()),
            new Task(
                function ($arg) {
                    return 'foo' . $arg;
                },
                'bar',
                null
            )
        );
    }

    /**
     * @test
     */
    public function setResultQueue()
    {

        $reflection = new \ReflectionProperty('\Ackintosh\Snidel\Worker', 'resultQueue');
        $reflection->setAccessible(true);

        $this->assertNull($reflection->getValue($this->worker));

        $queue = new Queue(getmypid());
        $this->worker->setResultQueue($queue);

        $this->assertInstanceOf('\Ackintosh\Snidel\Result\Queue', $reflection->getValue($this->worker));
    }

    /**
     * @test
     */
    public function runTask()
    {
        $queue = new Queue(getmypid());
        $this->worker->setResultQueue($queue);

        $this->worker->run();

        $result = $queue->dequeue();
        $this->assertSame('foobar', $result->getReturn());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function runThrowsExceptionWhenExceptionOccurredInTask()
    {
        $this->worker = new Worker(
            new Fork(getmypid()),
            new Task(
                function ($arg) {
                    throw new RuntimeException('test');
                },
                'bar',
                null
            )
        );

        $this->worker->run();
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function runThrowsExceptionWhenExceptionOccurredInQueue()
    {
        $queue = new Queue(getmypid());
        $property = new \ReflectionProperty('\Ackintosh\Snidel\Result\Queue', 'stat');
        $property->setAccessible(true);
        $stat   = $property->getValue($queue);
        $stat['msg_qbytes'] = 1;
        $property->setValue($queue, $stat);

        $this->worker->setResultQueue($queue);
        $this->worker->run();
    }

    /**
     * @test
     */
    public function error()
    {
        $queue = new Queue(getmypid());
        $this->worker->setResultQueue($queue);

        $this->worker->error();

        $result = $queue->dequeue();
        $this->assertTrue($result->isFailure());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function errorThrowsException()
    {
        $queue = new Queue(getmypid());
        $property = new \ReflectionProperty('\Ackintosh\Snidel\Result\Queue', 'stat');
        $property->setAccessible(true);
        $stat   = $property->getValue($queue);
        $stat['msg_qbytes'] = 1;
        $property->setValue($queue, $stat);

        $this->worker->setResultQueue($queue);
        $this->worker->error();
    }
}
