<?php
class Snidel_SharedMemoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->shm = new Snidel_SharedMemory(getmypid());
    }

    /**
     * @test
     * @expectedException Snidel_Exception_SharedMemoryControlException
     * @requires PHP 5.3
     */
    public function openThrowsExceptionWhenFailed()
    {
        $ref = new ReflectionProperty($this->shm, 'key');
        $ref->setAccessible(true);
        $originalSegmentId = $ref->getValue($this->shm);
        $ref->setValue($this->shm, INF);

        try {
            $this->shm->open();
        } catch (Snidel_Exception_SharedMemoryControlException $e) {
            $ref->setValue($this->shm, $originalSegmentId);
            $this->shm->delete();
            $this->shm->close($removeTmpFile = true);
            throw $e;
        }
    }

    /**
     * @test
     * @expectedException Snidel_Exception_SharedMemoryControlException
     * @requires PHP 5.3
     */
    public function writeThrowsExceptionWhenFailed()
    {
        $this->shm->open(10);
        $ref = new ReflectionProperty($this->shm, 'segmentId');
        $ref->setAccessible(true);
        $originalSegmentId = $ref->getValue($this->shm);
        $ref->setValue($this->shm, INF);

        try {
            $this->shm->write('foo');
        } catch (Snidel_Exception_SharedMemoryControlException $e) {
            $ref->setValue($this->shm, $originalSegmentId);
            $this->shm->delete();
            $this->shm->close($removeTmpFile = true);
            throw $e;
        }
    }

    /**
     * @test
     * @expectedException Snidel_Exception_SharedMemoryControlException
     * @requires PHP 5.3
     */
    public function readThrowsExceptionWhenFailed()
    {
        $this->shm->open(10);
        $this->shm->write('foo');
        $ref = new ReflectionProperty($this->shm, 'segmentId');
        $ref->setAccessible(true);
        $originalSegmentId = $ref->getValue($this->shm);
        $ref->setValue($this->shm, INF);

        try {
            $this->shm->read();
        } catch (Snidel_Exception_SharedMemoryControlException $e) {
            $ref->setValue($this->shm, $originalSegmentId);
            $this->shm->delete();
            $this->shm->close($removeTmpFile = true);
            throw $e;
        }
    }

    /**
     * @test
     * @expectedException Snidel_Exception_SharedMemoryControlException
     * @requires PHP 5.3
     */
    public function deleteThrowsExceptionWhenFailed()
    {
        $this->shm->open(10);
        $ref = new ReflectionProperty($this->shm, 'segmentId');
        $ref->setAccessible(true);
        $originalSegmentId = $ref->getValue($this->shm);
        $ref->setValue($this->shm, INF);

        try {
            $this->shm->delete();
        } catch (Snidel_Exception_SharedMemoryControlException $e) {
            $ref->setValue($this->shm, $originalSegmentId);
            $this->shm->delete();
            $this->shm->close($removeTmpFile = true);
            throw $e;
        }
    }
}
