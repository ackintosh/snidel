<?php
class Snidel_Token
{
    /**
     * @var int
     */
    private $ownerPid;

    /**
     * @var int
     */
    private $maxProcs;

    /**
     * @var resource
     */
    private $id;

    /**
     * @param   int     $ownerPid
     * @param   int     $maxProcs
     */
    public function __construct($ownerPid, $maxProcs)
    {
        $this->ownerPid = $ownerPid;
        $this->maxProcs = $maxProcs;
        $this->id = msg_get_queue($this->genId());
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
     */
    public function back()
    {
        return msg_send($this->id, 1, getmypid());// owner(parent) or child pid
    }

    /**
     * generate IPC key
     *
     * @return  int
     */
    private function genId()
    {
        $pathname = '/tmp/' . sha1($this->ownerPid);
        if (!file_exists($pathname)) {
            touch($pathname);
        }

        return ftok($pathname, 'S');
    }

    /**
     * initialize the queue of token
     *
     * @return void
     */
    private function initializeQueue()
    {
        for ($i = 0; $i < $this->maxProcs; $i++) {
            $this->back($this->ownerPid);
        }
    }

    public function __destruct()
    {
        if (getmypid() === $this->ownerPid) {
            return msg_remove_queue($this->id);
        }
    }
}
