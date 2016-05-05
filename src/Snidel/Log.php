<?php
namespace Ackintosh\Snidel;

date_default_timezone_set('UTC');

class Log
{
    /** @var int */
    private $ownerPid;

    /** @var int */
    private $masterPid;

    /** @var resource */
    private $destination;

    /**
     * @param   int     $ownerPid
     */
    public function __construct($ownerPid)
    {
        $this->ownerPid = $ownerPid;
    }

    public function setMasterPid($pid)
    {
        $this->masterPid = $pid;
    }

    /**
     * sets the resource for the log.
     *
     * @param   resource    $resource
     * @return  void
     */
    public function setDestination($resource)
    {
        $this->destination = $resource;
    }

    /**
     * writes log
     *
     * @param   string  $type
     * @param   string  $message
     * @return  void
     */
    private function write($type, $message)
    {
        if ($this->destination === null) {
            return;
        }
        $pid = getmypid();
        switch (true) {
        case $this->ownerPid === $pid:
            $role = 'owner';
            break;
        case $this->masterPid === $pid:
            $role = 'master';
            break;
        default:
            $role = 'worker';
            break;
        }
        fputs(
            $this->destination,
            sprintf(
                '[%s][%s][%d(%s)] %s',
                date('Y-m-d H:i:s'),
                $type,
                $pid,
                $role,
                $message . PHP_EOL
            )
        );
    }

    /**
     * writes log
     *
     * @param   string  $message
     * @return  void
     */
    public function info($message)
    {
        $this->write('info', $message);
    }

    /**
     * writes log
     *
     * @param   string  $message
     * @return  void
     */
    public function error($message)
    {
        $this->write('error', $message);
    }
}
