<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel;
use Ackintosh\Snidel\Token;

class TokenTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function accept()
    {
        $token = new Token(getmypid(), 1);
        $this->assertTrue($token->accept());
    }
}
