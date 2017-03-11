<?php
namespace Ackintosh\Snidel;

class LogTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Ackintosh\Snidel\Log */
    private $log;

    public function setUp()
    {
        $this->log = new Log(getmypid());
    }

    /**
     * @test
     */
    public function setdestination()
    {
        $fp = fopen('php://stdout', 'w');
        $this->log->setdestination($fp);
        $this->assertObjectHasAttribute('destination', $this->log);
        fclose($fp);
    }

    /**
     * @test
     */
    public function info()
    {
        $fp = fopen('php://temp', 'w');
        $this->log->setdestination($fp);
        $this->log->info('test');
        rewind($fp);
        $this->assertStringMatchesFormat('%s[info]%s', fgets($fp));
    }

    /**
     * @test
     */
    public function error()
    {
        $fp = fopen('php://temp', 'w');
        $this->log->setdestination($fp);
        $this->log->error('test');
        rewind($fp);
        $this->assertStringMatchesFormat('%s[error]%s', fgets($fp));
    }

    /**
     * @test
     */
    public function writeOwnerLog()
    {
        $fp = fopen('php://temp', 'w');
        $this->log->setDestination($fp);
        $this->log->info('test');
        rewind($fp);
        $this->assertStringMatchesFormat('%s(owner)%s', fgets($fp));
    }

    /**
     * @test
     */
    public function writeMasterLog()
    {
        $fp = fopen('php://temp', 'w');
        $this->log->setDestination($fp);

        $prop = new \ReflectionProperty('Ackintosh\Snidel\Log', 'ownerPid');
        $prop->setAccessible(true);
        $prop->setValue($this->log, 1);

        $prop = new \ReflectionProperty('Ackintosh\Snidel\Log', 'masterPid');
        $prop->setAccessible(true);
        $prop->setValue($this->log, getmypid());
        $this->log->info('test');
        rewind($fp);
        $this->assertStringMatchesFormat('%s(master)%s', fgets($fp));
    }

    /**
     * @test
     */
    public function writeWorkerLog()
    {
        $fp = fopen('php://temp', 'w');
        $this->log->setDestination($fp);

        $prop = new \ReflectionProperty('Ackintosh\Snidel\Log', 'ownerPid');
        $prop->setAccessible(true);
        $prop->setValue($this->log, 1);

        $prop = new \ReflectionProperty('Ackintosh\Snidel\Log', 'masterPid');
        $prop->setAccessible(true);
        $prop->setValue($this->log, 1);

        $this->log->info('test');
        rewind($fp);
        $this->assertStringMatchesFormat('%s(worker)%s', fgets($fp));
    }
}
