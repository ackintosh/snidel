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

    /**
     * @test
     */
    public function setError()
    {
        $result = new Result();

        $error = array('foo' => 'bar');
        $result->setError($error);

        $this->assertTrue($result->isFailure());
        $this->assertSame($error, $result->getError());
    }
}
