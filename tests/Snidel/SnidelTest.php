<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel;
use Ackintosh\Snidel\DataRepository;
use Ackintosh\Snidel\ForkContainer;
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

        $this->assertTrue($this->isSame($snidel->get()->toArray(), array('foo', 'bar')));
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function throwsExceptionWhenFailedToFork()
    {
        $taskQueue = $this->getMockBuilder('Ackintosh\Snidel\TaskQueue')
            ->setConstructorArgs(array(getmypid()))
            ->setMethods(array('enqueue'))
            ->getMock();
        $taskQueue->method('enqueue')
            ->will($this->throwException(new \RuntimeException()));

        $snidel = new Snidel();
        $snidel->fork('receivesArgumentsAndReturnsIt', array('bar'));
        $ref = new \ReflectionProperty($snidel, 'taskQueue');
        $ref->setAccessible(true);
        $ref->setValue($snidel, $taskQueue);

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
        $result = $snidel->get()->toArray();

        $this->assertSame(array_shift($result), 'foo');
    }

    /**
     * @test
     */
    public function passTheValueOtherThanArray()
    {
        $snidel = new Snidel();
        $snidel->fork('receivesArgumentsAndReturnsIt', 'foo');
        $result = $snidel->get()->toArray();

        $this->assertSame(array_shift($result), 'foo');
    }

    /**
     * @test
     */
    public function passMultipleArguments()
    {
        $snidel = new Snidel();
        $snidel->fork('receivesArgumentsAndReturnsIt', array('foo', 'bar'));
        $result = $snidel->get()->toArray();

        $this->assertSame(array_shift($result), 'foobar');
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
    public function getReturnsForkCollection()
    {
        $snidel = new Snidel();
        $snidel->fork(function () {
            return 'foo';
        });

        $this->assertInstanceOf('\Ackintosh\Snidel\ForkCollection', $snidel->get());
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

        $this->assertTrue($this->isSame($snidel->get()->toArray(), array('foo', 'bar')));
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

        $this->assertTrue($this->isSame($snidel->get()->toArray(), array('foo', 'bar')));
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

        $this->assertTrue($this->isSame($snidel->get('tag1')->toArray(), array('bar1', 'bar2')));
        $this->assertTrue($this->isSame($snidel->get('tag2')->toArray(), array('bar3', 'bar4')));
    }

    /**
     * @test
     */
    public function getOutput()
    {
        $snidel = new Snidel();
        $snidel->fork(function () {
            echo 'foobar';
        });
        $forks = $snidel->get();

        $this->assertSame('foobar', $forks[0]->getResult()->getOutput());
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
        $result = $snidel->run($snidel->map(array('FOO', 'BAR'), 'strtolower')
            ->then(function (\Ackintosh\Snidel\Fork $fork) {
                return ucfirst($fork->getResult()->getReturn());
            }));
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
        $snidel = new Snidel();
        $snidel->fork(function () {
            exit(1);
        });

        $snidel->wait();
        $this->assertTrue($snidel->hasError());
    }

    /**
     * @test
     * @requires PHP 5.4
     * @expectedException \Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function waitSimplyThrowsException()
    {
        $forkContainer = $this->getMockBuilder('Ackintosh\Snidel\ForkContainer')
            ->setMethods(array('wait'))
            ->getMock();
        $forkContainer->method('wait')
            ->will($this->throwException(new SharedMemoryControlException));

        $snidel = new Snidel();
        $ref = new \ReflectionProperty($snidel, 'forkContainer');
        $ref->setAccessible(true);
        $ref->setValue($snidel, $forkContainer);
        $snidel->forkSimply('receivesArgumentsAndReturnsIt', array('bar'));

        $snidel->waitSimply();
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

        $forkContainer = new ForkContainer();
        $ref = new \ReflectionProperty($forkContainer, 'pcntl');
        $ref->setAccessible(true);
        $ref->setValue($forkContainer, $pcntl);

        $snidel = new Snidel();
        $ref = new \ReflectionProperty($snidel, 'forkContainer');
        $ref->setAccessible(true);
        $ref->setValue($snidel, $forkContainer);
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

    private function isSame($result, $expect)
    {
        if (!is_array($result)) {
            return false;
        }

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
