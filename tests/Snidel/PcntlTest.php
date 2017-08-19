<?php
namespace Ackintosh\Snidel;

class PcntlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @runInSeparateProcess
     * @expectedException \RuntimeException
     */
    public function forkThrowsException()
    {
        require_once __DIR__ . '/../pcntl_fork.php';
        (new Pcntl())->fork();
    }
}
