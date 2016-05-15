<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Result\Result;

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

    /**
     * @test
     */
    public function setOutput()
    {
        $result = new Result();
        $result->setOutput('foo');
        $this->assertSame('foo', $result->getOutput());
    }
}
