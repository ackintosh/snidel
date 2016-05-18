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
        $fork = new Fork(getmypid(), new Task('receivesArgumentsAndReturnsIt', 'foo', null));
        $this->assertSame(getmypid(), $fork->getPid());
    }
}
