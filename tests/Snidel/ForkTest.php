<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Task\Task;
use Ackintosh\Snidel\Fork\Fork;

class ForkTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function pid()
    {
        $fork = new Fork(getmypid());
        $this->assertSame(getmypid(), $fork->getPid());
    }

    /**
     * @test
     */
    public function status()
    {
        $fork = new Fork(getmypid());

        $expect = 1;
        $fork->setStatus($expect);

        $this->assertSame($expect, $fork->getStatus());
    }
}
