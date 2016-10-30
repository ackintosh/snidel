<?php
use Ackintosh\Snidel\TestCase;
use Ackintosh\Snidel\Worker;
use Ackintosh\Snidel\Result\Queue;
use Ackintosh\Snidel\Fork\Fork;
use Ackintosh\Snidel\Task\Task;
use Ackintosh\Snidel\ActiveWorkerSet;
use Ackintosh\Snidel\Fork\Container;
use Ackintosh\Snidel\Log;

class ActiveWorkerSetTest extends TestCase
{
    /** @var \Ackintosh\Snidel\ActiveWorkerSet */
    private $activeWorderSet;

    public function setUp()
    {
        $this->activeWorkerSet = new ActiveWorkerSet();
    }

    /**
     * @test
     */
    public function add()
    {
        $ref = new \ReflectionProperty('\Ackintosh\Snidel\ActiveWorkerSet', 'workers');
        $ref->setAccessible(true);
        $workers = $ref->getValue($this->activeWorkerSet);

        $this->assertSame(array(), $workers);

        $worker = $this->makeWorker();
        $this->activeWorkerSet->add($worker);

        $workers = $ref->getValue($this->activeWorkerSet);
        $this->assertSame(array(getmypid() => $worker), $workers);
    }

    /**
     * @test
     */
    public function delete()
    {
        $worker1 = $this->makeWorker(1);
        $worker2 = $this->makeWorker(2);
        $this->activeWorkerSet->add($worker1);
        $this->activeWorkerSet->add($worker2);
        $this->activeWorkerSet->delete($worker1->getPid());

        $ref = new \ReflectionProperty('\Ackintosh\Snidel\ActiveWorkerSet', 'workers');
        $ref->setAccessible(true);
        $workers = $ref->getValue($this->activeWorkerSet);

        $this->assertSame(array(2 => $worker2), $workers);
    }

    /**
     * @test
     */
    public function countWorker()
    {
        $this->assertSame(0, $this->activeWorkerSet->count());

        $this->activeWorkerSet->add($this->makeWorker(1));
        $this->assertSame(1, $this->activeWorkerSet->count());

        $this->activeWorkerSet->add($this->makeWorker(2));
        $this->assertSame(2, $this->activeWorkerSet->count());
    }

    /**
     * @test
     */
    public function terminate()
    {
        $container = $this->makeForkContainer();

        $refProp = new \ReflectionProperty('\Ackintosh\Snidel\Fork\Container', 'masterPid');
        $refProp->setAccessible(true);
        $refProp->setValue($container, getmypid());

        $refMethod = new \ReflectionMethod('\Ackintosh\Snidel\Fork\Container', 'forkWorker');
        $refMethod->setAccessible(true);
        $worker1 = $refMethod->invokeArgs($container, array());
        $worker2 = $refMethod->invokeArgs($container, array());
        $this->activeWorkerSet->add($worker1);
        $this->activeWorkerSet->add($worker2);

        $this->activeWorkerSet->terminate(SIGTERM);

        // pcntl_wait with WUNTRACED returns `-1` if process has already terminated.
        $status = null;
        $this->assertSame(-1, pcntl_waitpid($worker1->getPid(), $status, WUNTRACED));
        $this->assertSame(-1, pcntl_waitpid($worker2->getPid(), $status, WUNTRACED));
    }
}
