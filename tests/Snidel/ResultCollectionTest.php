<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Result\Collection;
use Ackintosh\Snidel\Task\Task;

class ResultCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function arrayAccess()
    {
        $collection = new Collection(array());
        $this->assertNull($collection[0]);

        $collection[0] = new Result();
        $this->assertInstanceOf('\Ackintosh\Snidel\Result\Result', $collection[0]);

        unset($collection[0]);
        $this->assertNull($collection[0]);
    }

    /**
     * @test
     */
    public function implementsIteratorInterface()
    {
        $resultA = new Result();
        $resultA->setFork(new Fork($dummyPid = 100, new Task('receivesArgumentsAndReturnsIt', 'foo', null)));
        $resultB = new Result();
        $resultB->setFork(new Fork($dummyPid = 200, new Task('receivesArgumentsAndReturnsIt', 'foo', null)));

        $results = array(
            $resultA,
            $resultB,
        );
        $collection = new Collection($results);

        foreach ($collection as $position => $result) {
            $this->assertSame(($position + 1) * 100, $result->getFork()->getPid());
        }
    }
}
