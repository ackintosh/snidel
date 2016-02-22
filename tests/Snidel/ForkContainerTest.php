<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Fork;
use Ackintosh\Snidel\ForkContainer;

class ForkContainerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Ackintosh\Snidel\ForkContainer */
    private $forkContainer;

    /** @const int */
    const EXISTS        = 123;

    /** @const int */
    const NOT_EXISTS    = 456;

    public function setUp()
    {
        $this->forkContainer = new ForkContainer();
        $this->forkContainer[self::EXISTS] = new Fork(getmypid());
    }

    /**
     * @test
     */
    public function offsetExists()
    {
        $this->forkContainer = new ForkContainer();
        $this->forkContainer[self::EXISTS] = new Fork(getmypid());

        $this->assertTrue($this->forkContainer->offsetExists(self::EXISTS));
        $this->assertFalse($this->forkContainer->offsetExists(self::NOT_EXISTS));
    }

    /**
     * @test
     */
    public function offsetGet()
    {
        $this->assertNull($this->forkContainer->offsetGet(self::NOT_EXISTS));
    }

    /**
     * @test
     */
    public function offsetUnset()
    {
        unset($this->forkContainer[self::EXISTS]);
        $this->assertNull($this->forkContainer[self::EXISTS]);
    }
}
