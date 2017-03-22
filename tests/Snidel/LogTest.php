<?php
namespace Ackintosh\Snidel;

class LogTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function info()
    {
        $this->assertNull((new Log(getmypid(), null))->info('test'));

        $logger = $this->getMockBuilder('Psr\Log\NullLogger')
            ->setMethods(['debug'])
            ->getMock();
        $logger->expects($this->once())
            ->method('debug')
            ->with(
                $this->stringContains('[{role}] [{pid}] '),
                $this->equalTo(
                    ['role' => 'owner', 'pid' => getmypid()]
                )
            );

        (new Log(getmypid(), $logger))->info('test');
    }

    /**
     * @test
     */
    public function error()
    {
        $this->assertNull((new Log(getmypid(), null))->error('test'));

        $logger = $this->getMockBuilder('Psr\Log\NullLogger')
            ->setMethods(['error'])
            ->getMock();
        $logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('[{role}] [{pid}] '),
                $this->equalTo(
                    ['role' => 'owner', 'pid' => getmypid()]
                )
            );

        (new Log(getmypid(), $logger))->error('test');
    }
}
