<?php
namespace Ackintosh\Snidel\Result;

use Ackintosh\Snidel\Config;
use Ackintosh\Snidel\Result\Result;

interface QueueInterface
{
    /**
     * @param   \Ackintosh\Snidel\Result\Result
     * @throws  \RuntimeException
     */
    public function enqueue(Result $result);

    /**
     * @return  \Ackintosh\Snidel\Result\Result
     * @throws  \RuntimeException
     */
    public function dequeue();
}