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
}
