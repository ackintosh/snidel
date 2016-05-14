<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Task\Task;

class ForkTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function isQueued()
    {
        $fork = new Fork(getmypid(), new Task('receivesArgumentsAndReturnsIt', 'foo', null));
        $this->assertFalse($fork->isQueued());
    }
}
