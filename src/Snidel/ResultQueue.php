<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\IpcKey;
use Ackintosh\Snidel\Fork;
use Opis\Closure\SerializableClosure;

class ResultQueue
{

    const RESULT_MAX_SIZE = 5120;

    private $dequeuedCount = 0;

    public function __construct($ownerPid)
    {
        $this->ownerPid = $ownerPid;
        $this->ipcKey = new IpcKey($ownerPid, 'snidel_result_queue_');
        $this->id = msg_get_queue($this->ipcKey->generate());
    }

    /**
     * @param   \Ackintosh\Snidel\Fork
     */
    public function enqueue($fork)
    {
        return msg_send($this->id, 1, Fork::serialize($fork));
    }

    public function dequeue()
    {
        $this->dequeuedCount++;
        $msgtype = $serializedFork = null;
        $success = msg_receive($this->id, 1, $msgtype, self::RESULT_MAX_SIZE, $serializedFork);

        if (!$success) {
            throw new \RuntimeException('failed to dequeue result');
        }

        return Fork::unserialize($serializedFork);
    }

    /**
     * @return int
     */
    public function dequeuedCount()
    {
        return $this->dequeuedCount;
    }

    public function __destruct()
    {
        if ($this->ipcKey->isOwner(getmypid())) {
            $this->ipcKey->delete();
            return msg_remove_queue($this->id);
        }
    }// @codeCoverageIgnore
}
