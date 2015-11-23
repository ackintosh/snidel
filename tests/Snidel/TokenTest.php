<?php
/**
 * @runTestsInSeparateProcesses
 */
class Snidel_TokenTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function accept()
    {
        $token = new Snidel_Token(getmypid(), 1);
        $time = time();
        $token->accept();
        // no waiting time
        $this->assertSame(0, time() - $time);
        $token->back();

        $snidel = new Snidel();
        $snidel->fork('sleepsTwoSeconds', array(), null, $token);
        $snidel->fork('sleepsTwoSeconds', array(), null, $token);
        $time = time();
        $snidel->get();
        $this->assertSame(4, time() - $time);
    }
}
