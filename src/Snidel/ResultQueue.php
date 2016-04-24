<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\IpcKey;
use Opis\Closure\SerializableClosure;

class ResultQueue
{

    const RESULT_MAX_SIZE = 5120;

    private $dequeuedCount = 0;

    public function __construct($ownerPid)
    {
        $this->ownerPid = $ownerPid;
        $this->ipcKey = new IpcKey($ownerPid, 'snidel_result_' . uniqid((string) mt_rand(1, 100), true));
        $this->id = msg_get_queue($this->ipcKey->generate());
    }

    public function enqueue($result)
    {
        return msg_send($this->id, 1, serialize($result));
    }

    public function dequeue()
    {
        $this->dequeuedCount++;
        $msgtype = $message = null;
        $success = msg_receive($this->id, 1, $msgtype, self::RESULT_MAX_SIZE, $message);

        if (!$success) {
            throw new \RuntimeException('failed to dequeue result');
        }

        return unserialize($message);
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
