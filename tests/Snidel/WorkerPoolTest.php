<?php
declare(strict_types=1);

use Ackintosh\Snidel\WorkerPool;
use Ackintosh\Snidel\Config;
use Ackintosh\Snidel\TestCase;

class WorkerPoolTest extends TestCase
{
    /** @var \Ackintosh\Snidel\WorkerPool */
    private $workerPool;

    public function setUp()
    {
        $this->workerPool = new WorkerPool();
    }

    /**
     * @test
     */
    public function add()
    {
        $ref = new \ReflectionProperty('\Ackintosh\Snidel\WorkerPool', 'workers');
        $ref->setAccessible(true);
        $workers = $ref->getValue($this->workerPool);

        $this->assertSame([], $workers);

        $worker = $this->makeWorker();
        $this->workerPool->add($worker);

        $workers = $ref->getValue($this->workerPool);
        $this->assertSame([getmypid() => $worker], $workers);
    }

    /**
     * @test
     */
    public function delete()
    {
        $worker1 = $this->makeWorker(1);
        $worker2 = $this->makeWorker(2);
        $this->workerPool->add($worker1);
        $this->workerPool->add($worker2);
        $this->workerPool->delete($worker1->getPid());

        $ref = new \ReflectionProperty('\Ackintosh\Snidel\WorkerPool', 'workers');
        $ref->setAccessible(true);
        $workers = $ref->getValue($this->workerPool);

        $this->assertSame([2 => $worker2], $workers);
    }

    /**
     * @test
     */
    public function countWorker()
    {
        $this->assertSame(0, $this->workerPool->count());

        $this->workerPool->add($this->makeWorker(1));
        $this->assertSame(1, $this->workerPool->count());

        $this->workerPool->add($this->makeWorker(2));
        $this->assertSame(2, $this->workerPool->count());
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

        $this->workerPool->add($worker1);
        $this->workerPool->add($worker2);
        $this->workerPool->terminate(SIGTERM);
    }
}
