<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Map;

class MapTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->map = new Map('echo', 5);
    }

    /**
     * @test
     */
    public function getCallable()
    {
        $this->map = new Map('echo', 5);
        $this->assertSame('echo', $this->map->getCallable());
    }

    /**
     * @test
     */
    public function getToken()
    {
        $this->assertInstanceOf('Ackintosh\\Snidel\\Token', $this->map->getToken());
    }

    /**
     * @test
     */
    public function childPid()
    {
        $this->map->addChildPid(getmypid());
        $this->assertSame(array(getmypid()), $this->map->getChildPids());
    }

    /**
     * @test
     */
    public function hasChild()
    {
        $this->map->addChildPid(getmypid());
        $this->assertTrue($this->map->hasChild(getmypid()));
        $this->assertFalse($this->map->hasChild(getmypid() + 1));
    }

    /**
     * @test
     */
    public function isProcessing()
    {
        $this->assertTrue($this->map->isProcessing());

        $this->map->countTheForked();
        $this->assertTrue($this->map->isProcessing());

        $this->map->countTheCompleted();
        $this->assertFalse($this->map->isProcessing());
    }
}
