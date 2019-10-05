<?php
declare(strict_types=1);

namespace Ackintosh\Snidel;

class WorkerPool
{
    /** @var \Ackintosh\Snidel\Worker[] */
    private $workers = [];

    /**
     * @param   \Ackintosh\Snidel\Worker
     * @return  void
     */
    public function add($worker)
    {
        $this->workers[$worker->getPid()] = $worker;
    }

    /**
     * @param   int     $pid
     * @return  void
     */
    public function delete($pid)
    {
        unset($this->workers[$pid]);
    }

    /**
     * @return  int
     */
    public function count()
    {
        return count($this->workers);
    }

    /**
     * @param   int     $sig
     * @return  void
     */
    public function terminate($sig)
    {
        foreach ($this->workers as $worker) {
            $worker->terminate($sig);
        }
    }
}
