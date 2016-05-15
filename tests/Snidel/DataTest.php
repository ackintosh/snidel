<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Data;
use Ackintosh\Snidel\Fork;
use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Task\Task;
use Ackintosh\Snidel\Exception\SharedMemoryControlException;

class DataTest extends \PHPUnit_Framework_TestCase
{
    private function makeFork()
    {
        return new Fork(getmypid());
    }

    private function makeTask()
    {
        return new Task('receivesArgumentsAndReturnsIt', 'foo', null);
    }

    private function makeResult()
    {
        $result = new Result();
        $result->setFork($this->makeFork());
        $result->setTask($this->makeTask());

        return $result;
    }

    /**
     * @test
     */
    public function constructorSetsPid()
    {
        $data = new Data(1234);
        $ref = new \ReflectionProperty($data, 'pid');
        $ref->setAccessible(true);
        $this->assertSame($ref->getValue($data), 1234);
    }

    /**
     * @test
     */
    public function writeAndRead()
    {
        $data = new Data(getmypid());
        $result = $this->makeResult();
        $data->write($result);
        $this->assertEquals($data->readAndDelete(), $result);
    }

    /**
     * @test
     * @expectedException Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function readAndDeleteThrowsExceptionWhenFailed()
    {
        $shm = $this->getMockBuilder('Ackintosh\\Snidel\\SharedMemory')
            ->setConstructorArgs(array(getmypid()))
            ->setMethods(array('read'))
            ->getMock();

        $shm->expects($this->once())
            ->method('read')
            ->will($this->throwException(new SharedMemoryControlException));

        $data = new Data(getmypid());
        $ref = new \ReflectionProperty($data, 'shm');
        $ref->setAccessible(true);
        $ref->setValue($data, $shm);
        try {
            $data->write($this->makeResult());
            $data->readAndDelete();
        } catch (SharedMemoryControlException $e) {
            $data->delete();
            throw $e;
        }
    }

    /**
     * @test
     * @expectedException Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function writeThrowsRuntimeExceptionWhenFailedToOpenShm()
    {
        $shm = $this->getMockBuilder('Ackintosh\\Snidel\\SharedMemory')
            ->setConstructorArgs(array(getmypid()))
            ->setMethods(array('open'))
            ->getMock();

        $shm->method('open')
            ->will($this->throwException(new SharedMemoryControlException));

        $data = new Data(getmypid());
        $ref = new \ReflectionProperty($data, 'shm');
        $ref->setAccessible(true);
        $ref->setValue($data, $shm);
        try {
            $data->write($this->makeResult());
        } catch (SharedMemoryControlException $e) {
            $data->delete();
            throw $e;
        }
    }

    /**
     * @test
     * @expectedException Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function writeThrowsRuntimeExceptionWhenFailedToWriteData()
    {
        $shm = $this->getMockBuilder('Ackintosh\\Snidel\\SharedMemory')
            ->setConstructorArgs(array(getmypid()))
            ->setMethods(array('write'))
            ->getMock();

        $shm->expects($this->once())
            ->method('write')
            ->will($this->throwException(new SharedMemoryControlException));

        $data = new Data(getmypid());
        $ref = new \ReflectionProperty($data, 'shm');
        $ref->setAccessible(true);
        $ref->setValue($data, $shm);
        try {
            $data->write($this->makeResult());
        } catch (SharedMemoryControlException $e) {
            $data->delete();
            throw $e;
        }
    }

    /**
     * @test
     * @expectedException Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function readThrowsRuntimeException()
    {
        $shm = $this->getMockBuilder('Ackintosh\\Snidel\\SharedMemory')
            ->setConstructorArgs(array(getmypid()))
            ->setMethods(array('read'))
            ->getMock();

        $shm->expects($this->once())
            ->method('read')
            ->will($this->throwException(new SharedMemoryControlException));

        $data = new Data(getmypid());
        $data->write($this->makeResult());
        $ref = new \ReflectionProperty($data, 'shm');
        $ref->setAccessible(true);
        $ref->setValue($data, $shm);
        try {
            $data->read();
        } catch (SharedMemoryControlException $e) {
            $data->delete();
            throw $e;
        }
    }

    /**
     * @test
     * @expectedException Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function deleteThrowsExceptionWhenFailedToOpenShm()
    {
        $shm = $this->getMockBuilder('Ackintosh\\Snidel\\SharedMemory')
            ->setConstructorArgs(array(getmypid()))
            ->setMethods(array('open'))
            ->getMock();

        $shm->expects($this->once())
            ->method('open')
            ->will($this->throwException(new SharedMemoryControlException));

        $data = new Data(getmypid());
        $data->write($this->makeResult());
        $ref = new \ReflectionProperty($data, 'shm');
        $ref->setAccessible(true);
        $originalShm = $ref->getValue($data);

        $ref->setValue($data, $shm);
        try {
            $data->delete();
        } catch (SharedMemoryControlException $e) {
            $ref->setValue($data, $originalShm);
            $data->delete();
            throw $e;
        }
    }

    /**
     * @test
     * @expectedException Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function deleteThrowsExceptionWhenFailedToDeleteShm()
    {
        $shm = $this->getMockBuilder('Ackintosh\\Snidel\\SharedMemory')
            ->setConstructorArgs(array(getmypid()))
            ->setMethods(array('delete'))
            ->getMock();

        $shm->expects($this->once())
            ->method('delete')
            ->will($this->throwException(new SharedMemoryControlException));

        $data = new Data(getmypid());
        $data->write($this->makeResult());
        $ref = new \ReflectionProperty($data, 'shm');
        $ref->setAccessible(true);
        $originalShm = $ref->getValue($data);

        $ref->setValue($data, $shm);
        try {
            $data->delete();
        } catch (SharedMemoryControlException $e) {
            $ref->setValue($data, $originalShm);
            $data->delete();
            throw $e;
        }
    }

    /**
     * @test
     */
    public function deleteIfExistsReturnsNull()
    {
        $data = new Data(getmypid());
        $this->assertNull($data->deleteIfExists());

        $data->write($this->makeResult());
        $this->assertNull($data->deleteIfExists());
    }

    /**
     * @test
     * @expectedException Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function deleteIfExistsThrowsExceptionWhenFailedToDeleteShm()
    {
        $shm = $this->getMockBuilder('Ackintosh\Snidel\SharedMemory')
            ->setConstructorArgs(array(getmypid()))
            ->setMethods(array('delete'))
            ->getMock();

        $shm->expects($this->once())
            ->method('delete')
            ->will($this->throwException(new SharedMemoryControlException));

        $data = new Data(getmypid());
        $data->write($this->makeResult());
        $ref = new \ReflectionProperty($data, 'shm');
        $ref->setAccessible(true);
        $originalShm = $ref->getValue($data);

        $ref->setValue($data, $shm);
        try {
            $data->deleteIfExists();
        } catch (SharedMemoryControlException $e) {
            $ref->setValue($data, $originalShm);
            $data->delete();
            throw $e;
        }
    }
}
