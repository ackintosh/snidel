<?php
declare(strict_types=1);

namespace Ackintosh\Snidel\Fork;

class Process
{
    /** @var int */
    private $pid;

    /** @var int */
    private $status;

    /**
     * @param   int     $pid
     */
    public function __construct(int $pid)
    {
        $this->pid = $pid;
    }

    /**
     * set exit status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * return pid
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * return exit status
     */
    public function getStatus(): int
    {
        return $this->status;
    }
}
