<?php
use Ackintosh\Snidel\Worker;
use Ackintosh\Snidel\Result\Queue;
use Ackintosh\Snidel\Fork\Fork;
use Ackintosh\Snidel\Task\Task;
use Ackintosh\Snidel\ActiveWorkerSet;

class ActiveWorkerSetTest extends \PHPUnit_Framework_TestCase
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
    public function toArray()
    {
        $worker1 = $this->makeWorker(1);
        $worker2 = $this->makeWorker(2);
        $worker3 = $this->makeWorker(3);
        $this->activeWorkerSet->add($worker1);
        $this->activeWorkerSet->add($worker2);
        $this->activeWorkerSet->add($worker3);

        $expect = array(
            1 => $worker1,
            2 => $worker2,
            3 => $worker3,
        );

        $this->assertSame($expect, $this->activeWorkerSet->toArray());
    }

    private function makeWorker($pid = null)
    {
        $pid = $pid ?: getmypid();

        return new Worker(
            new Fork($pid),
            new Task(
                function ($arg) {
                    return 'foo' . $arg;
                },
                'bar',
                null
            )
        );
    }
}
