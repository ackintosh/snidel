<?php
use Ackintosh\Snidel\Task\MinifiedTask;

class MinifiedTaskTest extends \PHPUnit_Framework_TestCase
{
    /** @var Ackintosh\Snidel\Task\MinifiedTask */
    private $minifiedTask;

    public function setUp()
    {
        $this->minifiedTask = new MinifiedTask('testTag');
    }

    /**
     * @test
     */
    public function getCallable()
    {
        $this->assertNull($this->minifiedTask->getCallable());
    }

    /**
     * @test
     */
    public function getArgs()
    {
        $this->assertNull($this->minifiedTask->getArgs());
    }

    /**
     * @test
     */
    public function getTag()
    {
        $this->assertSame('testTag', $this->minifiedTask->getTag());
    }
}
