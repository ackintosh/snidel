<?php
namespace Ackintosh\Snidel;

class IpcKeyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @runInSeparateProcess
     * @expectedException \RuntimeException
     */
    public function generateThrowsException()
    {
        require_once __DIR__ . '/../ftok.php';
        $ipcKey = new IpcKey(getmypid());
        $ipcKey->generate();
    }

}
