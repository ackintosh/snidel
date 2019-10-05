<?php
declare(strict_types=1);

namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Fork\Process;

class Pcntl
{
    /**
     * @see pcntl_fork
     * @return Process
     * @throws \RuntimeException
     */
    public function fork()
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
    public function signal($signo, $handler, $restart_syscall = true)
    {
        return pcntl_signal($signo, $handler, $restart_syscall);
    }

    /**
     * @see pcntl_waitpid
     */
    public function waitpid($pid, &$status, $options = 0)
    {
        return pcntl_waitpid($pid, $status, $options);
    }
}
