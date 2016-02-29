<?php
namespace Ackintosh\Snidel;

class IpcKey
{
    /** @var int */
    private $ownerPid;

    /** @var string */
    private $prefix;

    /**
     * @param   int     $ownerPid
     * @param   string  $prefix
     */
    public function __construct($ownerPid, $prefix = '')
    {
        $this->ownerPid = $ownerPid;
        $this->prefix = $prefix;
    }

    /**
     * generate IPC key
     *
     * @return  int
     */
    public function generate()
    {
        $pathname = '/tmp/' . $this->prefix . $this->ownerPid;
        if (!file_exists($pathname)) {
            touch($pathname);
        }

        return ftok($pathname, 'S');
    }

    /**
     * @param   int     $pid
     * @return  bool
     */
    public function isOwner($pid)
    {
        return $pid === $this->ownerPid;
    }

    /**
     * delete tmp file
     *
     * @return  void
     */
    public function delete()
    {
        unlink('/tmp/' . $this->prefix . $this->ownerPid);
    }
}
