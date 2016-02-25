<?php
namespace Ackintosh\Snidel;

class ResultTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function setReturn()
    {
        $result = new Result();
        $result->setReturn('foo');
        $this->assertSame('foo', $result->getReturn());
    }
}
