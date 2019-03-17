<?php
use Ackintosh\Snidel\Task\Task;
use Ackintosh\Snidel\TestCase;

class CoordinatorTest extends TestCase
{
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

        $coordinator = $this->makeForkCoordinator();
        $coordinator->pcntl = $pcntl;
        $coordinator->forkWorker();
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

        $coordinator = $this->makeForkCoordinator();
        $coordinator->pcntl = $pcntl;
        $coordinator->forkMaster();
    }

    /**
     * @test
     */
    public function sendSignalToMaster()
    {
        $coordinator = $this->makeForkCoordinator();
        $master = $coordinator->forkMaster();
        $coordinator->sendSignalToMaster(SIGTERM);

        // pcntl_wait with WUNTRACED returns `-1` if process has already terminated.
        $status = null;
        $this->assertSame(-1, pcntl_waitpid($master->getPid(), $status, WUNTRACED));
    }
}
