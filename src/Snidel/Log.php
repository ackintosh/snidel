<?php
class Snidel_Log
{
    /** @var int */
    private $ownerPid;

    /** @var resource */
    private $destination;

    /**
     * @param   int     $ownerPid
     */
    public function __construct($ownerPid)
    {
        $this->ownerPid = $ownerPid;
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
        fputs(
            $this->destination,
            sprintf(
                '[%s][%s][%d(%s)] %s',
                date('Y-m-d H:i:s'),
                $type,
                $pid,
                ($this->ownerPid === $pid) ? 'p' : 'c',
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
