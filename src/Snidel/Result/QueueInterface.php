<?php
namespace Ackintosh\Snidel\Result;

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
