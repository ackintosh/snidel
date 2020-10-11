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

    /** @var  \Psr\Log\LoggerInterface|null */
    private $logger;

    public function __construct(int $ownerPid, ?LoggerInterface $logger)
    {
        $this->ownerPid = $ownerPid;
        $this->logger = $logger;
    }

    /**
     * @param int $pid
     * @return void
     */
    public function setMasterPid(int $pid)
    {
        $this->masterPid = $pid;
    }

    /**
     * creates context
     */
    private function context(): array
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
     */
    public function info(string $message): void
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->debug($message, $this->context());
    }

    /**
     * error
     */
    public function error(string $message): void
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->error($message, $this->context());
    }
}
