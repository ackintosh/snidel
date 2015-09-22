<?php
class SnidelTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function forkProcessAndReceiveValues()
    {
        $snidel = new Snidel();

        $func = function ($arg) {
            return $arg;
        };

        $snidel->fork($func, array('foo'));
        $snidel->fork($func, array('bar'));
        $snidel->join();

        $this->assertSame($snidel->get(), array('foo', 'bar'));
    }

    /**
     * @test
     */
    public function omitTheSecondArgumentOfFork()
    {
        $snidel = new Snidel();

        $func = function () {
            return 'foo';
        };

        $snidel->fork($func);
        $snidel->join();

        $this->assertSame($snidel->get(), array('foo'));
    }

    /**
     * @test
     */
    public function passTheValueOtherThanArray()
    {
        $snidel = new Snidel();

        $func = function ($arg) {
            return $arg;
        };

        $snidel->fork($func, 'foo');
        $snidel->join();

        $this->assertSame($snidel->get(), array('foo'));
    }

    /**
     * @test
     */
    public function passMultipleArguments()
    {
        $snidel = new Snidel();

        $func = function ($arg1, $arg2) {
            return $arg1 . $arg2;
        };

        $snidel->fork($func, array('foo', 'bar'));
        $snidel->join();

        $this->assertSame($snidel->get(), array('foobar'));
    }
}
