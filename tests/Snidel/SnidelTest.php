<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel;
use Ackintosh\Snidel\DataRepository;
use Ackintosh\Snidel\Exception\SharedMemoryControlException;

/**
 * @runTestsInSeparateProcesses
 */
class SnidelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function forkProcessAndReceiveValues()
    {
        $snidel = new Snidel();

        $snidel->fork('receivesArgumentsAndReturnsIt', array('foo'));
        $snidel->fork('receivesArgumentsAndReturnsIt', array('bar'));

        $this->assertTrue($this->isSame($snidel->get(), array('foo', 'bar')));
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function throwsExceptionWhenFailedToFork()
    {
        $pcntl = $this->getMockBuilder('Ackintosh\Snidel\Pcntl')
            ->setMethods(array('fork'))
            ->getMock();
        $pcntl->method('fork')
            ->willReturn(-1);

        $snidel = new Snidel();
        $ref = new \ReflectionProperty($snidel, 'pcntl');
        $ref->setAccessible(true);
        $ref->setValue($snidel, $pcntl);

        try {
            $snidel->fork('receivesArgumentsAndReturnsIt', array('bar'));
        } catch (\RuntimeException $e) {
            $snidel->wait();
            throw $e;
        }
    }

    /**
     * @test
     */
    public function omitTheSecondArgumentOfFork()
    {
        $snidel = new Snidel();

        $snidel->fork('returnsFoo');

        $this->assertSame($snidel->get(), array('foo'));
    }

    /**
     * @test
     */
    public function passTheValueOtherThanArray()
    {
        $snidel = new Snidel();

        $snidel->fork('receivesArgumentsAndReturnsIt', 'foo');

        $this->assertSame($snidel->get(), array('foo'));
    }

    /**
     * @test
     */
    public function passMultipleArguments()
    {
        $snidel = new Snidel();

        $snidel->fork('receivesArgumentsAndReturnsIt', array('foo', 'bar'));

        $this->assertSame($snidel->get(), array('foobar'));
    }

    /**
     * @test
     */
    public function concurrency()
    {
        $concurrency = 3;
        $snidel = new Snidel($concurrency);

        $start = time();
        $snidel->fork('sleepsTwoSeconds');
        $snidel->fork('sleepsTwoSeconds');
        $snidel->fork('sleepsTwoSeconds');
        $snidel->fork('sleepsTwoSeconds');
        $snidel->get();
        $elapsed = time() - $start;

        $this->assertTrue(4 <= $elapsed && $elapsed < 6);
    }

    /**
     * @test
     */
    public function runInstanceMethod()
    {
        $snidel = new Snidel();
        $test = new \TestClass();

        $snidel->fork(array($test, 'returnsFoo'));
        $snidel->fork(array($test, 'receivesArgumentsAndReturnsIt'), 'bar');

        $this->assertTrue($this->isSame($snidel->get(), array('foo', 'bar')));
    }

    /**
     * @test
     */
    public function runAnonymousFunction()
    {
        $snidel = new Snidel();
        $func = function ($arg = 'foo') {
            return $arg;
        };
        $snidel->fork($func);
        $snidel->fork($func, 'bar');

        $this->assertTrue($this->isSame($snidel->get(), array('foo', 'bar')));
    }

    /**
     * @test
     */
    public function getResultsWithTag()
    {
        $snidel = new Snidel();
        $test = new \TestClass();

        $snidel->fork(array($test, 'receivesArgumentsAndReturnsIt'), 'bar1', 'tag1');
        $snidel->fork(array($test, 'receivesArgumentsAndReturnsIt'), 'bar2', 'tag1');
        $snidel->fork(array($test, 'receivesArgumentsAndReturnsIt'), 'bar3', 'tag2');
        $snidel->fork(array($test, 'receivesArgumentsAndReturnsIt'), 'bar4', 'tag2');

        $this->assertTrue($this->isSame($snidel->get('tag1'), array('bar1', 'bar2')));
        $this->assertTrue($this->isSame($snidel->get('tag2'), array('bar3', 'bar4')));
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function throwsExceptionWhenPassedUnknownTag()
    {
        $snidel = new Snidel();
        $test = new \TestClass();

        $snidel->fork(array($test, 'receivesArgumentsAndReturnsIt'), 'bar', 'tag');
        $snidel->get('unknown_tag');
    }

    /**
     * @test
     */
    public function mapRun()
    {
        $snidel = new Snidel();
        $result = $snidel->run($snidel->map(array('FOO', 'BAR'), 'strtolower')->then('ucfirst'));
        $this->isSame($result, array('Foo', 'Bar'));
    }

    /**
     * @test
     */
    public function abnormalExit()
    {
        $snidel = new Snidel();
        $snidel->fork('abnormalExit');
        $snidel->wait();

        $this->assertTrue($snidel->hasError());
    }

    /**
     * @test
     */
    public function waitSetsErrorWhenChildTerminatesAbnormally()
    {
        // wifexited
        $pcntl = $this->getMockBuilder('Ackintosh\Snidel\Pcntl')
            ->setMethods(array('wifexited'))
            ->getMock();
        $pcntl->method('wifexited')
            ->willReturn(false);

        $snidel = new Snidel();
        $snidel->fork('receivesArgumentsAndReturnsIt', array('bar'));
        $ref = new \ReflectionProperty($snidel, 'pcntl');
        $ref->setAccessible(true);
        $ref->setValue($snidel, $pcntl);
        $snidel->wait();

        $this->assertTrue($snidel->hasError());

        // wexitstatus
        $pcntl = $this->getMockBuilder('Ackintosh\Snidel\Pcntl')
            ->setMethods(array('wexitstatus'))
            ->getMock();
        $pcntl->method('wexitstatus')
            ->willReturn(1);

        $snidel = new Snidel();
        $snidel->fork('receivesArgumentsAndReturnsIt', array('bar'));
        $ref = new \ReflectionProperty($snidel, 'pcntl');
        $ref->setAccessible(true);
        $ref->setValue($snidel, $pcntl);
        $snidel->wait();

        $this->assertTrue($snidel->hasError());
    }

    /**
     * @test
     * @expectedException \Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function waitThrowsException()
    {
        $data = $this->getMockBuilder('Ackintosh\Snidel\Data')
            ->setConstructorArgs(array(getmypid()))
            ->setMethods(array('readAndDelete'))
            ->getMock();
        $data->method('readAndDelete')
            ->will($this->throwException(new SharedMemoryControlException));

        $dataRepository = $this->getMockBuilder('Ackintosh\Snidel\DataRepository')
            ->setMethods(array('load'))
            ->getMock();
        $dataRepository->expects($this->any())
            ->method('load')
            ->willReturn($data);

        $snidel = new Snidel();
        $ref = new \ReflectionProperty($snidel, 'dataRepository');
        $ref->setAccessible(true);
        $originalDataRepository = $ref->getValue($snidel);
        $ref->setValue($snidel, $dataRepository);
        $snidel->fork('receivesArgumentsAndReturnsIt', array('bar'));

        try {
            $snidel->wait();
        } catch (SharedMemoryControlException $e) {
            // clean up
            $data->delete();
            // set original DataRepository
            $ref->setValue($snidel, $originalDataRepository);
            throw $e;
        }
    }

    /**
     * @test
     */
    public function waitDoNothingIfAlreadyJoined()
    {
        $snidel = new Snidel();
        $snidel->fork('receivesArgumentsAndReturnsIt', array('bar'));
        $snidel->wait();
        $ret =  $snidel->wait();
        $this->assertNull($ret);
    }

    /**
     * @test
     */
    public function getErrorReturnsInstanceOfSnidelError()
    {
        $snidel = new Snidel();
        $snidel->wait();
        $this->assertInstanceOf('Ackintosh\\Snidel\\Error', $snidel->getError());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function runThrowsExceptionWhenFailedToFork()
    {
        $pcntl = $this->getMockBuilder('Ackintosh\\Snidel\\Pcntl')
            ->setMethods(array('fork'))
            ->getMock();
        $pcntl->method('fork')
            ->willReturn(-1);

        $snidel = new Snidel();
        $ref = new \ReflectionProperty($snidel, 'pcntl');
        $ref->setAccessible(true);
        $ref->setValue($snidel, $pcntl);
        try {
            $result = $snidel->run($snidel->map(array('FOO', 'BAR'), 'strtolower')->then('ucfirst'));
        } catch (\RuntimeException $e) {
            $snidel->wait();
            throw $e;
        }
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function runThrowsExceptionWhenErrorOccurredInChildProcess()
    {
        $snidel = new Snidel();
        try {
            $result = $snidel->run($snidel->map(array('FOO', 'BAR'), 'strtolower')->then(function () {
                exit(1);
            }));
        } catch (\RuntimeException $e) {
            unset($snidel);
            throw $e;
        }
    }

    /**
     * @test
     */
    public function childShutdownFunctionOutputsLog()
    {
        $log = $this->getMockBuilder('Ackintosh\Snidel\Log')
            ->setConstructorArgs(array(getmypid()))
            ->setMethods(array('info'))
            ->getMock();
        $log->expects($this->once())
            ->method('info');

        $snidel = new Snidel();
        $snidel->fork('receivesArgumentsAndReturnsIt', array('foo'));

        $ref = new \ReflectionProperty($snidel, 'log');
        $ref->setAccessible(true);
        $originalLog = $ref->getValue($snidel);

        $ref->setValue($snidel, $log);
        $snidel->childShutdownFunction();
        $ref->setValue($snidel, $originalLog);

        // delete the shared memory which opened in childShutdownFunction.
        $dataRepository = new DataRepository();
        $dataRepository->load(getmypid())->delete();

        $snidel->wait();
    }

    /**
     * @test
     * @expectedException \Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function childShutdownFunctionThrowsExceptionWhenFailedToWriteData()
    {
        $data = $this->getMockBuilder('Ackintosh\Snidel\Data')
            ->setConstructorArgs(array(getmypid()))
            ->setMethods(array('write'))
            ->getMock();
        $data->method('write')
            ->will($this->throwException(new SharedMemoryControlException));

        $dataRepository = $this->getMockBuilder('Ackintosh\Snidel\DataRepository')
            ->setMethods(array('load'))
            ->getMock();
        $dataRepository->expects($this->any())
            ->method('load')
            ->willReturn($data);

        $snidel = new Snidel();
        $snidel->fork('receivesArgumentsAndReturnsIt', array('bar'));

        $ref = new \ReflectionProperty($snidel, 'dataRepository');
        $ref->setAccessible(true);
        $originalDataRepository = $ref->getValue($snidel);
        $ref->setValue($snidel, $dataRepository);

        try {
            $snidel->childShutdownFunction();
        } catch (SharedMemoryControlException $e) {
            $ref->setValue($snidel, $originalDataRepository);
            $snidel->wait();
            throw $e;
        }
    }

    private function isSame($result, $expect)
    {
        foreach ($result as $r) {
            if ($keys = array_keys($expect, $r, true)) {
                unset($expect[$keys[0]]);
            } else {
                return false;
            }
        }

        return true;
    }
}
