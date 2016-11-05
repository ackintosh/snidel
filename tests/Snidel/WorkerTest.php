<?php
use Ackintosh\Snidel\TestCase;
use Ackintosh\Snidel\Worker;
use Ackintosh\Snidel\Result\Queue;
use Ackintosh\Snidel\Fork\Fork;
use Ackintosh\Snidel\Task\Task;
use Ackintosh\Snidel\Fork\Container;
use Ackintosh\Snidel\Log;

class WorkerTest extends TestCase
{
    public function setUp()
    {
        $this->worker = new Worker(new Fork(getmypid()));
    }

    /**
     * @test
     */
    public function setResultQueue()
    {

        $reflection = new \ReflectionProperty('\Ackintosh\Snidel\Worker', 'resultQueue');
        $reflection->setAccessible(true);

        $this->assertNull($reflection->getValue($this->worker));

        $this->worker->setResultQueue($this->makeResultQueue());
        $this->assertInstanceOf('\Ackintosh\Snidel\Result\Queue', $reflection->getValue($this->worker));
    }

    /**
     * @test
     */
    public function getPid()
    {
        $this->assertSame(getmypid(), $this->worker->getPid());
    }

    /**
     * @test
     */
    public function runTask()
    {
        $resultQueue = $this->makeResultQueue();
        $taskQueue = $this->makeTaskQueue();
        $this->worker->setResultQueue($resultQueue);
        $this->worker->setTaskQueue($taskQueue);
        $taskQueue->enqueue($this->makeTask());

        $this->worker->run();

        $result = $resultQueue->dequeue();
        $this->assertSame('foo', $result->getReturn());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function runThrowsExceptionWhenExceptionOccurredInTask()
    {
        $this->worker = new Worker(new Fork(getmypid()));
        $resultQueue = $this->makeResultQueue();
        $taskQueue = $this->makeTaskQueue();
        $this->worker->setResultQueue($resultQueue);
        $this->worker->setTaskQueue($taskQueue);
        $task = new Task(
            function ($arg) {
                throw new RuntimeException('test');
            },
            'bar',
            null
        );
        $taskQueue->enqueue($task);

        $this->worker->run();
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function runThrowsExceptionWhenExceptionOccurredInQueue()
    {
        $queue = $this->makeResultQueue();
        $property = new \ReflectionProperty('\Ackintosh\Snidel\Result\Queue', 'stat');
        $property->setAccessible(true);
        $stat   = $property->getValue($queue);
        $stat['msg_qbytes'] = 1;
        $property->setValue($queue, $stat);

        $this->worker->setResultQueue($queue);
        $taskQueue = $this->makeTaskQueue();
        $this->worker->setTaskQueue($taskQueue);
        $taskQueue->enqueue($this->makeTask());
        $this->worker->run();
    }

    /**
     * @test
     */
    public function error()
    {
        $queue = $this->makeResultQueue();
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
        $queue = $this->makeResultQueue();
        $property = new \ReflectionProperty('\Ackintosh\Snidel\Result\Queue', 'stat');
        $property->setAccessible(true);
        $stat   = $property->getValue($queue);
        $stat['msg_qbytes'] = 1;
        $property->setValue($queue, $stat);

        $this->worker->setResultQueue($queue);
        $this->worker->error();
    }

    /**
     * @test
     */
    public function terminate()
    {
        $container = $this->makeForkContainer();
        $container->masterPid = getmypid();
        $worker = $container->forkWorker();
        $worker->terminate(SIGTERM);

        // pcntl_wait with WUNTRACED returns `-1` if process has already terminated.
        $status = null;
        $this->assertSame(-1, pcntl_waitpid($worker->getPid(), $status, WUNTRACED));
    }
}
