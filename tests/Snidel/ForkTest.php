<?php
namespace Ackintosh\Snidel;

class ForkTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException \Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function getResultThrowsExceptionWhenFailedControlShm()
    {
        $fork = new Fork(getmypid());

        $ref = new \ReflectionProperty($fork, 'pid');
        $ref->setAccessible(true);
        $ref->setValue($fork, 0);

        $fork->getResult();
    }
}
