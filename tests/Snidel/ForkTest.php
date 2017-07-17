<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Fork\Process;

class ForkTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function pid()
    {
        $fork = new Process(getmypid());
        $this->assertSame(getmypid(), $fork->getPid());
    }

    /**
     * @test
     */
    public function status()
    {
        $fork = new Process(getmypid());

        $expect = 1;
        $fork->setStatus($expect);

        $this->assertSame($expect, $fork->getStatus());
    }
}
