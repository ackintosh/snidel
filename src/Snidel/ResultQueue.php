<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\AbstractQueue;
use Ackintosh\Snidel\Fork;

class ResultQueue extends AbstractQueue
{
    /**
     * @param   \Ackintosh\Snidel\Fork
     */
    public function enqueue($fork)
    {
        return $this->sendMessage(Fork::serialize($fork));
    }

    /**
     * @return  \Ackintosh\Snidel\Fork
     * @throws  \RuntimeException
     */
    public function dequeue()
    {
        $this->dequeuedCount++;
        try {
        $serializedFork = $this->receiveMessage();
        } catch (\RuntimeException $e) {
            throw new \RuntimeException('failed to dequeue result');
        }

        return Fork::unserialize($serializedFork);
    }
}
