<?php
namespace Ackintosh\Snidel\Result;

use Ackintosh\Snidel\AbstractQueue;
use Ackintosh\Snidel\Fork\Fork;
use Ackintosh\Snidel\Result\Formatter as ResultFormatter;
use Ackintosh\Snidel\Result\QueueInterface;

class Queue extends AbstractQueue implements QueueInterface
{
    /**
     * @param   \Ackintosh\Snidel\Result\Result
     * @throws  \RuntimeException
     */
    public function enqueue($result)
    {
        if (
            $this->isExceedsLimit($serialized = ResultFormatter::serialize($result))
            && $this->isExceedsLimit($serialized = ResultFormatter::minifyAndSerialize($result))
        ) {
            throw new \RuntimeException('the fork which includes result exceeds the message queue limit.');
        }

        return $this->sendMessage($serialized);
    }

    /**
     * @return  \Ackintosh\Snidel\Result\Result
     * @throws  \RuntimeException
     */
    public function dequeue()
    {
        $this->dequeuedCount++;
        try {
        $serialized = $this->receiveMessage();
        } catch (\RuntimeException $e) {
            throw new \RuntimeException('failed to dequeue result');
        }

        return ResultFormatter::unserialize($serialized);
    }
}
