<?php
use Ackintosh\Snidel\WorkerPool;
use Ackintosh\Snidel\Config;
use Ackintosh\Snidel\TestCase;

class ActiveWorkerSetTest extends TestCase
{
    /** @var \Ackintosh\Snidel\WorkerPool */
    private $activeWorkerSet;

    public function setUp()
    {
        $this->activeWorkerSet = new WorkerPool();
    }

    /**
     * @test
     */
    public function add()
    {
        $ref = new \ReflectionProperty('\Ackintosh\Snidel\WorkerPool', 'workers');
        $ref->setAccessible(true);
        $workers = $ref->getValue($this->activeWorkerSet);

        $this->assertSame([], $workers);

        $worker = $this->makeWorker();
        $this->activeWorkerSet->add($worker);

        $workers = $ref->getValue($this->activeWorkerSet);
        $this->assertSame([getmypid() => $worker], $workers);
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

        $ref = new \ReflectionProperty('\Ackintosh\Snidel\WorkerPool', 'workers');
        $ref->setAccessible(true);
        $workers = $ref->getValue($this->activeWorkerSet);

        $this->assertSame([2 => $worker2], $workers);
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
        $driver = (new Config())->get('driver');
        $worker1 = $this->getMockBuilder('\Ackintosh\Snidel\Worker')
            ->setConstructorArgs([$this->makeProcess(1), $driver, 1])
            ->setMethods(['terminate'])
            ->getMock();
        $worker1->expects($this->once())
            ->method('terminate')
            ->with(SIGTERM);

        $worker2 = $this->getMockBuilder('\Ackintosh\Snidel\Worker')
            ->setConstructorArgs([$this->makeProcess(2), $driver, 1])
            ->setMethods(['terminate'])
            ->getMock();
        $worker2->expects($this->once())
            ->method('terminate')
            ->with(SIGTERM);

        $this->activeWorkerSet->add($worker1);
        $this->activeWorkerSet->add($worker2);
        $this->activeWorkerSet->terminate(SIGTERM);
    }
}
