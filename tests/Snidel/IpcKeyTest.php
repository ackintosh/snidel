<?php
namespace Ackintosh\Snidel;

class IpcKeyTest extends TestCase
{
    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function generateThrowsException()
    {
        $semaphore = $this->getMockBuilder('\Ackintosh\Snidel\Semaphore')
            ->setMethods(['ftok'])
            ->getMock();
        $semaphore->expects($this->once())
            ->method('ftok')
            ->willReturn(-1);

        $ipcKey = $this->setSemaphore(new IpcKey(getmypid()), $semaphore);
        $ipcKey->generate();
    }
}
