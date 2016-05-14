<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Task\Task;

class ForkTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function executeTask()
    {
        $fork = new Fork(getmypid(), new Task('receivesArgumentsAndReturnsIt', 'foo', null));
        $this->assertInstanceOf('Ackintosh\Snidel\Result', $fork->executeTask());
    }
}
