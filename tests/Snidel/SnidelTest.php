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
}
