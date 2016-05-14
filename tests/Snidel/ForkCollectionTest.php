<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Fork;
use Ackintosh\Snidel\ForkCollection;
use Ackintosh\Snidel\Task\Task;

class ForkCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function arrayAccess()
    {
        $forkCollection = new ForkCollection(array());
        $this->assertNull($forkCollection[0]);

        $forkCollection[0] = new Fork($dummyPid = 123, new Task('receivesArgumentsAndReturnsIt', 'foo', null));
        $this->assertInstanceOf('\Ackintosh\Snidel\Fork', $forkCollection[0]);

        unset($forkCollection[0]);
        $this->assertNull($forkCollection[0]);
    }

    /**
     * @test
     */
    public function implementsIteratorInterface()
    {
        $forks = array(
            new Fork($dummyPid = 100, new Task('receivesArgumentsAndReturnsIt', 'foo', null)),
            new Fork($dummyPid = 200, new Task('receivesArgumentsAndReturnsIt', 'foo', null)),
        );
        $forkCollection = new ForkCollection($forks);

        foreach ($forkCollection as $position => $fork) {
            $this->assertSame(($position + 1) * 100, $fork->getPid());
        }
    }
}
