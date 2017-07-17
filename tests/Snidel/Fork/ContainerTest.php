<?php
use Ackintosh\Snidel\Task\Task;
use Ackintosh\Snidel\TestCase;

class ContainerTest extends TestCase
{
    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function enqueueThrowsExceptionWhenFailed()
    {
        $semaphore = $this->getMockBuilder('\Ackintosh\Snidel\Semaphore')
            ->setMethods(['sendMessage'])
            ->getMock();
        $semaphore->expects($this->once())
            ->method('sendMessage')
            ->willReturn(false);

        $container = $this->makeForkContainer();
        $container->taskQueue = $this->setSemaphore($this->makeTaskQueue(), $semaphore);

        $task = new Task(
            function ($args) {
                return $args;
            }, 
            'foo',
            null
        );

        $container->enqueue($task);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function forkWorkerThrowsExceptionWhenFailed()
    {
        $pcntl = $this->getMockBuilder('\Ackintosh\Snidel\Pcntl')
            ->setMethods(['fork'])
            ->getMock();

        $pcntl->expects($this->once())
            ->method('fork')
            ->willThrowException(new \RuntimeException());

        $container = $this->makeForkContainer();
        $container->pcntl = $pcntl;
        $container->forkWorker();
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function forkMasterThrowsExceptionWhenFailed()
    {
        $pcntl = $this->getMockBuilder('\Ackintosh\Snidel\Pcntl')
            ->setMethods(['fork'])
            ->getMock();

        $pcntl->expects($this->once())
            ->method('fork')
            ->willThrowException(new \RuntimeException());

        $container = $this->makeForkContainer();
        $container->pcntl = $pcntl;
        $container->forkMaster();
    }

    /**
     * @test
     */
    public function sendSignalToMaster()
    {
        $container = $this->makeForkContainer();
        $masterPid = $container->forkMaster();
        $container->sendSignalToMaster(SIGTERM);

        // pcntl_wait with WUNTRACED returns `-1` if process has already terminated.
        $status = null;
        $this->assertSame(-1, pcntl_waitpid($masterPid, $status, WUNTRACED));
    }
}
