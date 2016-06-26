<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel;
use Ackintosh\Snidel\DataRepository;
use Ackintosh\Snidel\Fork\Container;
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
        $snidel = new Snidel();
        $ref = new \ReflectionProperty($snidel, 'log');
        $ref->setAccessible(true);
        $log = $ref->getValue($snidel);

        $container = $this->getMockBuilder('Ackintosh\Snidel\Fork\Container')
            ->setConstructorArgs(array(getmypid(), $log))
            ->setMethods(array('enqueue'))
            ->getMock();
        $container->method('enqueue')
            ->will($this->throwException(new \RuntimeException()));

        $ref = new \ReflectionProperty($snidel, 'container');
        $ref->setAccessible(true);
        $ref->setValue($snidel, $container);

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

        $this->assertEquals(4, $elapsed, '', 1);
    }

    /**
     * @test
     */
    public function getReturnsResultCollection()
    {
        $snidel = new Snidel();
        $snidel->fork(function () {
            return 'foo';
        });

        $this->assertInstanceOf('\Ackintosh\Snidel\Result\Collection', $snidel->get());
    }

    /**
     * @test
     */
    public function getReturnsEachResult()
    {
        $snidel = new Snidel();

        $snidel->fork(function () {
            return 'foo';
        });
        $collection = $snidel->get();
        $this->assertSame('foo', $collection[0]->getReturn());

        $snidel->fork(function () {
            return 'bar';
        });
        $collection = $snidel->get();
        $this->assertSame('bar', $collection[0]->getReturn());
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
        $collection = $snidel->get();
        $this->assertSame('foobar', $collection[0]->getOutput());
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
