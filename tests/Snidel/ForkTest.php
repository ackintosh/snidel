<?php
namespace Ackintosh\Snidel;

class ForkTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function isQueued()
    {
        $fork = new Fork(getmypid());
        $this->assertFalse($fork->isQueued());
    }
}
