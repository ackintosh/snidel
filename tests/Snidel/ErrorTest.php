<?php
declare(strict_types=1);

namespace Ackintosh\Snidel;

class ErrorTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->err = new Error();
    }

    /**
     * @test
     */
    public function offsetExists()
    {
        $this->assertFalse($this->err->offsetExists('foo'));
        $this->err['foo'] = 'bar';
        $this->assertTrue($this->err->offsetExists('foo'));
    }

    /**
     * @test
     */
    public function offsetGet()
    {
        $this->assertNull($this->err->offsetGet('foo'));
        $this->err['foo'] = 'bar';
        $this->assertSame('bar', $this->err->offsetGet('foo'));
    }

    /**
     * @test
     */
    public function offsetUnset()
    {
        $this->err['foo'] = 'bar';
        $this->assertSame('bar', $this->err->offsetGet('foo'));
        $this->err->offsetUnset('foo');
        $this->assertNull($this->err['foo']);
    }
}
