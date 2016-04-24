<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel;
use Ackintosh\Snidel\Token;

/**
 * @runTestsInSeparateProcesses
 */
class TokenTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function accept()
    {
        $token = new Token(getmypid(), 1);
        $time = time();
        $token->accept();
        // no waiting time
        $this->assertSame(0, time() - $time);
        $token->back();

        $snidel = new Snidel();
        $snidel->forkSimply('sleepsTwoSeconds', array(), null, $token);
        $snidel->forkSimply('sleepsTwoSeconds', array(), null, $token);
        $time = time();
        $snidel->getSimply();
        $this->assertSame(4, time() - $time);
    }
}
