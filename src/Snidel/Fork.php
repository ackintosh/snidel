<?php
namespace Ackintosh\Snidel;

class Fork
{
    /** @var int */
    private $pid;

    /** @var int */
    private $status;

    /**
     * @param   int     $pid
     */
    public function __construct($pid)
    {
        $this->pid = $pid;
    }

    /**
     * set exit status
     *
     * @param   int     $status
     * @return  void
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * return pid
     *
     * @return  int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * return exit status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param   \Ackintosh\Snidel\Fork  $fork
     * @return  string
     */
    public static function serialize($fork)
    {
        return serialize($fork);
    }

    /**
     * @param   string  $serializedFork
     * @return  \Ackintosh\Snidel\Fork
     */
    public static function unserialize($serializedFork)
    {
        return unserialize($serializedFork);
    }
}
