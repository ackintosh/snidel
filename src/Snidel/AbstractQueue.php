<?php
declare(ticks=1);

namespace Ackintosh\Snidel;

abstract class AbstractQueue
{
    /** @var int */
    protected $ownerPid;

    /** @var \Ackintosh\Snidel\IpcKey */
    protected $ipcKey;

    /** @var \Ackintosh\Snidel\Semaphore */
    protected $semaphore;

    /** @var resource */
    protected $id;

    /** @var array */
    protected $stat;

    /** @var int */
    protected $queuedCount = 0;

    /** @var int */
    protected $dequeuedCount = 0;

    public function __construct(Config $config)
    {
        $this->ownerPid = $config->get('ownerPid');
        $this->ipcKey   = new IpcKey($this->ownerPid, spl_object_hash($config) . str_replace('\\', '_', get_class($this)));
        $this->semaphore = new Semaphore();
        $this->id       = $this->semaphore->getQueue($this->ipcKey->generate());
        $this->stat     = $this->semaphore->statQueue($this->id);
    }

    /**
     * @param   string  $message
     */
    protected function sendMessage($message)
    {
        return $this->semaphore->sendMessage($this->id, 1, $message, false);
    }

    /**
     * @return  string
     * @throws  \RuntimeException
     */
    protected function receiveMessage()
    {
        $msgtype = $message = null;

        // argument #3: specify the maximum number of bytes allowsed in one message queue.
        $success = $this->semaphore->receiveMessage($this->id, 1, $msgtype, $this->stat['msg_qbytes'], $message, false);
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

    /**
     * @return bool
     */
    public function delete()
    {
        $this->ipcKey->delete();
        return msg_remove_queue($this->id);
    }

    public function __destruct()
    {
        if (isset($this->ipcKey) && $this->ipcKey->isOwner(getmypid())) {
            $this->delete();
        }
    }// @codeCoverageIgnore
}
