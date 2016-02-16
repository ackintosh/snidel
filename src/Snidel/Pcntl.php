<?php
namespace Ackintosh\Snidel;

class Pcntl
{
    /**
     * @see pcntl_fork
     */
    public function fork()
    {
        return pcntl_fork();
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

    /**
     * @see pcntl_wifexited
     */
    public function wifexited($status)
    {
        return pcntl_wifexited($status);
    }

    /**
     * @see pcntl_wexitstatus()
     */
    public function wexitstatus($status)
    {
        return pcntl_wexitstatus($status);
    }
}
