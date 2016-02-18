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
        $snidel->fork('sleepsTwoSeconds', array(), null, $token);
        $snidel->fork('sleepsTwoSeconds', array(), null, $token);
        $time = time();
        $snidel->get();
        $this->assertSame(4, time() - $time);
    }

    /**
     * @test
     */
    public function destructorRemovesTmpFile()
    {
        $token = new Token(getmypid(), 1);
        $method = new \ReflectionMethod($token, 'getKey');
        $method->setAccessible(true);
        $key = $method->invoke($token);
        unset($token);
        $this->assertFalse(file_exists('/tmp/' . sha1($key)));
    }
}
