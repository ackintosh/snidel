<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\IpcKey;

class Token
{
    /** @var int */
    private $ownerPid;

    /** @var int */
    private $concurrency;

    /** @var string */
    private $keyPrefix;

    /** @var resource */
    private $id;

    /** @var \Ackintosh\Snidel\IpcKey */
    private $ipcKey;

    /**
     * @param   int     $ownerPid
     * @param   int     $concurrency
     */
    public function __construct($ownerPid, $concurrency)
    {
        $this->ownerPid = $ownerPid;
        $this->concurrency = $concurrency;
        $this->ipcKey = new IpcKey($ownerPid, 'snidel_token_' . uniqid((string) mt_rand(1, 100), true));
        $this->id = msg_get_queue($this->ipcKey->generate());
        $this->initializeQueue();
    }

    /**
     * wait for the token
     *
     * @return bool
     */
    public function accept()
    {
        $msgtype = $message = null;
        $success = msg_receive($this->id, 1, $msgtype, 100, $message, true, MSG_NOERROR);
        return $success;
    }

    /**
     * returns the token
     *
     * @return  bool
     */
    public function back()
    {
        // argument #3 is owner(parent) pid or child pid
        return msg_send($this->id, 1, getmypid());
    }

    /**
     * initialize the queue of token
     *
     * @return void
     */
    private function initializeQueue()
    {
        for ($i = 0; $i < $this->concurrency; $i++) {
            $this->back();
        }
    }

    public function __destruct()
    {
        if ($this->ipcKey->isOwner(getmypid())) {
            $this->ipcKey->delete();
            return msg_remove_queue($this->id);
        }
    }// @codeCoverageIgnore
}
