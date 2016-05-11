<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\IpcKey;

abstract class AbstractQueue
{
    /** @var int */
    protected $ownerPid;

    /** @var \Ackintosh\Snidel\IpcKey */
    protected $ipcKey;

    /** @var resource */
    protected $id;

    /** @var array */
    protected $stat;

    /** @var int */
    protected $queuedCount = 0;

    /** @var int */
    protected $dequeuedCount = 0;

    public function __construct($ownerPid)
    {
        $this->ownerPid = $ownerPid;
        $this->ipcKey   = new IpcKey($this->ownerPid, str_replace('\\', '_', get_class($this)));
        $this->id       = msg_get_queue($this->ipcKey->generate());
        $this->stat     = msg_stat_queue($this->id);
    }

    /**
     * @param   string  $message
     */
    protected function sendMessage($message)
    {
        return msg_send($this->id, 1, $message, false);
    }

    /**
     * @return  string
     * @throws  \RuntimeException
     */
    protected function receiveMessage()
    {
        $msgtype = $message = null;

        // argument #3: specify the maximum number of bytes allowsed in one message queue.
        $success = msg_receive($this->id, 1, $msgtype, $this->stat['msg_qbytes'], $message, false);
        if (!$success) {
            throw new \RuntimeException('failed to receive message.');
        }

        return $message;
    }

    /**
     * @param   string  $message
     * @return  bool
     */
    protected function isExceedsLimit($message)
    {
        return $this->stat['msg_qbytes'] < strlen($message);
    }

    /**
     * @return  int
     */
    public function queuedCount()
    {
        return $this->queuedCount;
    }

    /**
     * @return  int
     */
    public function dequeuedCount()
    {
        return $this->dequeuedCount;
    }

    abstract public function enqueue($something);
    abstract public function dequeue();

    public function __destruct()
    {
        if ($this->ipcKey->isOwner(getmypid())) {
            $this->ipcKey->delete();
            return msg_remove_queue($this->id);
        }
    }// @codeCoverageIgnore
}
