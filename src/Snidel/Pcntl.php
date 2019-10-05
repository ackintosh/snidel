<?php
declare(strict_types=1);

namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Fork\Process;

class Pcntl
{
    /**
     * @see pcntl_fork
     * @throws \RuntimeException
     */
    public function fork(): Process
    {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new \RuntimeException(pcntl_strerror(pcntl_get_last_error()));
        }

        $pid = ($pid === 0) ? getmypid() : $pid;
        return new Process($pid);
    }

    /**
     * @see pcntl_signal
     */
    public function signal(int $signo, callable $handler, bool $restart_syscall = true): bool
    {
        return pcntl_signal($signo, $handler, $restart_syscall);
    }

    /**
     * @see pcntl_waitpid
     */
    public function waitpid(int $pid, ?int &$status, int $options = 0): int
    {
        return pcntl_waitpid($pid, $status, $options);
    }
}
