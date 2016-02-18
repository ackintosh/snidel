<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\SharedMemory;
use Ackintosh\Snidel\Exception\SharedMemoryControlException;

class SharedMemoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->shm = new SharedMemory(getmypid());
    }

    /**
     * @test
     * @expectedException Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function openThrowsExceptionWhenFailed()
    {
        $ref = new \ReflectionProperty($this->shm, 'key');
        $ref->setAccessible(true);
        $originalSegmentId = $ref->getValue($this->shm);
        $ref->setValue($this->shm, INF);

        try {
            $this->shm->open();
        } catch (SharedMemoryControlException $e) {
            $ref->setValue($this->shm, $originalSegmentId);
            $this->shm->delete();
            $this->shm->close($removeTmpFile = true);
            throw $e;
        }
    }

    /**
     * @test
     * @expectedException Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function writeThrowsExceptionWhenFailed()
    {
        $this->shm->open(10);
        $ref = new \ReflectionProperty($this->shm, 'segmentId');
        $ref->setAccessible(true);
        $originalSegmentId = $ref->getValue($this->shm);
        $ref->setValue($this->shm, INF);

        try {
            $this->shm->write('foo');
        } catch (SharedMemoryControlException $e) {
            $ref->setValue($this->shm, $originalSegmentId);
            $this->shm->delete();
            $this->shm->close($removeTmpFile = true);
            throw $e;
        }
    }

    /**
     * @test
     * @expectedException Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function readThrowsExceptionWhenFailed()
    {
        $this->shm->open(10);
        $this->shm->write('foo');
        $ref = new \ReflectionProperty($this->shm, 'segmentId');
        $ref->setAccessible(true);
        $originalSegmentId = $ref->getValue($this->shm);
        $ref->setValue($this->shm, INF);

        try {
            $this->shm->read();
        } catch (SharedMemoryControlException $e) {
            $ref->setValue($this->shm, $originalSegmentId);
            $this->shm->delete();
            $this->shm->close($removeTmpFile = true);
            throw $e;
        }
    }

    /**
     * @test
     * @expectedException Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function deleteThrowsExceptionWhenFailed()
    {
        $this->shm->open(10);
        $ref = new \ReflectionProperty($this->shm, 'segmentId');
        $ref->setAccessible(true);
        $originalSegmentId = $ref->getValue($this->shm);
        $ref->setValue($this->shm, INF);

        try {
            $this->shm->delete();
        } catch (SharedMemoryControlException $e) {
            $ref->setValue($this->shm, $originalSegmentId);
            $this->shm->delete();
            $this->shm->close($removeTmpFile = true);
            throw $e;
        }
    }
}
