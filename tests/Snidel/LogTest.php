<?php
class Snidel_LogTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function setdestination()
    {
        $log = new Snidel_Log(getmypid());
        $fp = fopen('php://stdout', 'w');
        $log->setdestination($fp);
        $this->assertObjectHasAttribute('destination', $log);
        fclose($fp);
    }

    /**
     * @test
     */
    public function info()
    {
        $log = new Snidel_Log(getmypid());
        $fp = fopen('php://temp', 'w');
        $log->setdestination($fp);
        $log->info('test');
        rewind($fp);
        $this->assertStringMatchesFormat('%s[info]%s', fgets($fp));
    }

    /**
     * @test
     */
    public function error()
    {
        $log = new Snidel_Log(getmypid());
        $fp = fopen('php://temp', 'w');
        $log->setdestination($fp);
        $log->error('test');
        rewind($fp);
        $this->assertStringMatchesFormat('%s[error]%s', fgets($fp));
    }
}
