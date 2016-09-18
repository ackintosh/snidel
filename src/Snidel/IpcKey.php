<?php
namespace Ackintosh\Snidel;

class IpcKey
{
    /** @var int */
    private $ownerPid;

    /** @var string */
    private $prefix;

    /** @var string */
    private $pathname;

    /**
     * @param   int     $ownerPid
     * @param   string  $prefix
     */
    public function __construct($ownerPid, $prefix = '')
    {
        $this->ownerPid = $ownerPid;
        $this->prefix   = $prefix;
        $this->pathname = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->prefix . $this->ownerPid;
    }

    /**
     * generate IPC key
     *
     * @return  int
     */
    public function generate()
    {
        if (!file_exists($this->pathname)) {
            touch($this->pathname);
        }

        return ftok($this->pathname, 'S');
    }

    /**
     * check whether a pid is owner
     *
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
        if (file_exists($this->pathname)) {
            unlink($this->pathname);
        }
    }
}
