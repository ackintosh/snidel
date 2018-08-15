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

        $snidel->process('receivesArgumentsAndReturnsIt', ['foo']);
        $snidel->process('receivesArgumentsAndReturnsIt', ['bar']);

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
        $snidel->process('returnsFoo');

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
        $snidel->process('receivesArgumentsAndReturnsIt', 'foo');

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
        $snidel->process('receivesArgumentsAndReturnsIt', ['foo', 'bar']);

        foreach ($snidel->results() as $r) {
            $this->assertSame('foobar', $r->getReturn());
        }
    }

    /**
     * @test
     */
    public function concurrency()
    {
        $snidel = new Snidel([
            'concurrency' => 3,
            // in order to minify the delay time due to the issue of bernard's polling, specifying a small number.
            'pollingDuration' => 0.5,
        ]);

        $start = time();
        $snidel->process('sleepsTwoSeconds');
        $snidel->process('sleepsTwoSeconds');
        $snidel->process('sleepsTwoSeconds');
        $snidel->process('sleepsTwoSeconds');
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

        $snidel->process([$test, 'returnsFoo']);
        $snidel->process([$test, 'receivesArgumentsAndReturnsIt'], 'bar');

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
        $snidel->process($func);
        $snidel->process($func, 'bar');

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
        $snidel->process(function () {
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
        $snidel->process('abnormalExit');
        $snidel->wait();

        $this->assertTrue($snidel->hasError());
    }

    /**
     * @test
     */
    public function getSetsErrorWhenChildTerminatesAbnormally()
    {
        $snidel = new Snidel();
        $snidel->process(function () {
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
        $snidel->process('receivesArgumentsAndReturnsIt', ['bar']);
        $snidel->wait();
        $this->assertInstanceOf('Ackintosh\\Snidel\\Error', $snidel->getError());
    }

    /**
     * @test
     */
    public function results()
    {
        $snidel = new Snidel();
        $snidel->process('receivesArgumentsAndReturnsIt', ['foo']);
        $snidel->process('receivesArgumentsAndReturnsIt', ['bar']);

        foreach ($snidel->results() as $r) {
            $this->assertContains($r->getReturn(), ['foo', 'bar']);
        }
    }
}
