<?php
declare(strict_types=1);

namespace Ackintosh\Snidel;

class WorkerPool
{
    /** @var \Ackintosh\Snidel\Worker[] */
    private $workers = [];

    public function add(Worker $worker): void
    {
        $this->workers[$worker->getPid()] = $worker;
    }

    public function delete(int $pid): void
    {
        unset($this->workers[$pid]);
    }

    public function count(): int
    {
        return count($this->workers);
    }

    public function terminate(int $sig): void
    {
        foreach ($this->workers as $worker) {
            $worker->terminate($sig);
        }
    }
}
