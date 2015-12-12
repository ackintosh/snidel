<?php
/**
 * @runTestsInSeparateProcesses
 */
class Snidel_MapContainerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->mapContainer = new Snidel_MapContainer(array('foo', 'bar'), 'echo', 5);
    }

    /**
     * @test
     */
    public function then()
    {
        $return = $this->mapContainer->then('var_dump');
        $this->assertSame($this->mapContainer, $return);
    }

    /**
     * @test
     */
    public function getFirstMap()
    {
        $this->mapContainer->then('var_dump');
        $this->assertInstanceOf('Snidel_Map', $this->mapContainer->getFirstMap());
        $this->assertSame('echo', $this->mapContainer->getFirstMap()->getCallable());
    }

    /**
     * @test
     */
    public function getFirstArgs()
    {
        $this->assertSame(array('foo', 'bar'), $this->mapContainer->getFirstArgs());
    }

    /**
     * @test
     * @expectedException RuntimeException
     */
    public function nextMapThrowsExceptionWhenChildPidNotFound()
    {
        $this->mapContainer->nextMap(0);
    }
}
