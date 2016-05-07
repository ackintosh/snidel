<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\AbstractQueue;
use Ackintosh\Snidel\Fork;

class ResultQueue extends AbstractQueue
{
    /**
     * @param   \Ackintosh\Snidel\Fork
     * @throws  \RuntimeException
     */
    public function enqueue($fork)
    {
        $serialized = Fork::serialize($fork);
        if ($this->isExceedsLimit($serialized)) {
            throw new \RuntimeException('the fork which includes result exceeds the message queue limit.');
        }

        return $this->sendMessage($serialized);
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
