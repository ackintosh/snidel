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

    /** @var \Ackintosh\Snidel\Semaphore */
    private $semaphore;

    /**
     * @param   int     $ownerPid
     * @param   string  $prefix
     */
    public function __construct($ownerPid, $prefix = '')
    {
        $this->ownerPid = $ownerPid;
        $this->prefix   = $prefix;
        $this->pathname = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->prefix . $this->ownerPid;
        $this->semaphore = new Semaphore();
    }

    /**
     * generate IPC key
     *
     * @return  int
     * @throws \RuntimeException
     */
    public function generate()
    {
        if (!file_exists($this->pathname)) {
            touch($this->pathname);
        }

        if (($key = $this->semaphore->ftok($this->pathname, 'S')) === -1) {
            throw new \RuntimeException('failed to create System V IPC key');
        }

        return $key;
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
