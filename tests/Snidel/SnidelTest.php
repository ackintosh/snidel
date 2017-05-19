<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel;
use Ackintosh\Snidel\DataRepository;

class SnidelTest extends TestCase
{
    /**
     * @test
     */
    public function forkProcessAndReceiveValues()
    {
        $snidel = new Snidel();

        $snidel->fork('receivesArgumentsAndReturnsIt', ['foo']);
        $snidel->fork('receivesArgumentsAndReturnsIt', ['bar']);

        foreach ($snidel->results() as $r) {
            $this->assertContains($r->getReturn(), ['foo', 'bar']);
        }
    }

    /**
     * @test
     */
    public function omitTheSecondArgumentOfFork()
    {
        $snidel = new Snidel();
        $snidel->fork('returnsFoo');

        foreach ($snidel->results() as $r) {
            $this->assertSame('foo', $r->getReturn());
        }
    }

    /**
     * @test
     */
    public function passTheValueOtherThanArray()
    {
        $snidel = new Snidel();
        $snidel->fork('receivesArgumentsAndReturnsIt', 'foo');

        foreach ($snidel->results() as $r) {
            $this->assertSame('foo', $r->getReturn());
        }
    }

    /**
     * @test
     */
    public function passMultipleArguments()
    {
        $snidel = new Snidel();
        $snidel->fork('receivesArgumentsAndReturnsIt', ['foo', 'bar']);

        foreach ($snidel->results() as $r) {
            $this->assertSame('foobar', $r->getReturn());
        }
    }

    /**
     * @test
     */
    public function concurrency()
    {
        $snidel = new Snidel(['concurrency' => 3]);

        $start = time();
        $snidel->fork('sleepsTwoSeconds');
        $snidel->fork('sleepsTwoSeconds');
        $snidel->fork('sleepsTwoSeconds');
        $snidel->fork('sleepsTwoSeconds');
        $snidel->wait();
        $elapsed = time() - $start;

        $this->assertEquals(4, $elapsed, '', 1);
    }

    /**
     * @test
     */
    public function runInstanceMethod()
    {
        $snidel = new Snidel();
        $test = new \TestClass();

        $snidel->fork([$test, 'returnsFoo']);
        $snidel->fork([$test, 'receivesArgumentsAndReturnsIt'], 'bar');

        foreach ($snidel->results() as $r) {
            $this->assertContains($r->getReturn(), ['foo', 'bar']);
        }
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

        foreach ($snidel->results() as $r) {
            $this->assertContains($r->getReturn(), ['foo', 'bar']);
        }
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

        foreach ($snidel->results() as $r) {
            $this->assertSame('foobar', $r->getOutput());
        }
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
    public function getSetsErrorWhenChildTerminatesAbnormally()
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
    public function getErrorReturnsInstanceOfSnidelError()
    {
        $snidel = new Snidel();
        $snidel->fork('receivesArgumentsAndReturnsIt', ['bar']);
        $snidel->wait();
        $this->assertInstanceOf('Ackintosh\\Snidel\\Error', $snidel->getError());
    }

    /**
     * @test
     */
    public function setReceivedSignal()
    {
        $expect = 1;
        $snidel = new Snidel();
        $snidel->setReceivedSignal($expect);

        $prop = new \ReflectionProperty($snidel, 'receivedSignal');
        $prop->setAccessible(true);

        $this->assertSame($expect, $prop->getValue($snidel));
    }

    /**
     * @test
     */
    public function results()
    {
        $snidel = new Snidel();
        $snidel->fork('receivesArgumentsAndReturnsIt', ['foo']);
        $snidel->fork('receivesArgumentsAndReturnsIt', ['bar']);

        foreach ($snidel->results() as $r) {
            $this->assertContains($r->getReturn(), ['foo', 'bar']);
        }
    }
}
