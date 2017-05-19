<?php
use Ackintosh\Snidel\TestCase;
use Ackintosh\Snidel\ActiveWorkerSet;

class ActiveWorkerSetTest extends TestCase
{
    /** @var \Ackintosh\Snidel\ActiveWorkerSet */
    private $activeWorkerSet;

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

        $ref = new \ReflectionProperty('\Ackintosh\Snidel\ActiveWorkerSet', 'workers');
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
        $worker1 = $this->getMockBuilder('\Ackintosh\Snidel\Worker')
            ->setConstructorArgs([$this->makeFork(1)])
            ->setMethods(['terminate'])
            ->getMock();
        $worker1->expects($this->once())
            ->method('terminate')
            ->with(SIGTERM);

        $worker2 = $this->getMockBuilder('\Ackintosh\Snidel\Worker')
            ->setConstructorArgs([$this->makeFork(2)])
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
