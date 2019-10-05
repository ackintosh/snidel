<?php
declare(strict_types=1);

namespace Ackintosh\Snidel;

use Psr\Log\LoggerInterface;

date_default_timezone_set('UTC');

class Log
{
    /** @var int */
    private $ownerPid;

    /** @var int */
    private $masterPid;

    /** @var  \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * @param   int                     $ownerPid
     * @param   LoggerInterface | null  $logger
     */
    public function __construct($ownerPid, $logger)
    {
        $this->ownerPid = $ownerPid;
        $this->logger = $logger;
    }

    /**
     * @param int $pid
     * @return void
     */
    public function setMasterPid($pid)
    {
        $this->masterPid = $pid;
    }

    /**
     * creates context
     *
     * @return array
     */
    private function context()
    {
        $pid  = getmypid();
        switch ($pid) {
            case $this->ownerPid:
                $role = 'owner';
                break;
            case $this->masterPid:
                $role = 'master';
                break;
            default:
                $role = 'worker';
                break;
        }

        return [
            'role' => $role,
            'pid'  => $pid,
        ];
    }

    /**
     * info
     *
     * @param   string  $message
     * @return  void
     */
    public function info($message)
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->debug($message, $this->context());
    }

    /**
     * error
     *
     * @param   string  $message
     * @return  void
     */
    public function error($message)
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->error($message, $this->context());
    }
}
