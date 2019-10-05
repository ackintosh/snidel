<?php
declare(strict_types=1);

use Ackintosh\Snidel\Config;
use Ackintosh\Snidel\TestCase;
use Ackintosh\Snidel\Worker;
use Ackintosh\Snidel\Fork\Process;

class WorkerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->worker = $this->makeWorker();
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
    public function terminate()
    {
        $coordinator = $this->makeForkCoordinator();
        $coordinator->master = new Process(getmypid());
        $worker = $coordinator->forkWorker();
        $worker->terminate(SIGTERM);

        // pcntl_wait with WUNTRACED returns `-1` if process has already terminated.
        $status = null;
        $this->assertSame(-1, pcntl_waitpid($worker->getPid(), $status, WUNTRACED));
    }
}
