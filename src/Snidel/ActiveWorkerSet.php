<?php
namespace Ackintosh\Snidel;

class ActiveWorkerSet
{
    /** @var \Ackintosh\Snidel\Worker[] */
    private $workers = array();

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
     * @return  \Ackintosh\Snidel\Worker[]
     */
    public function toArray()
    {
        return $this->workers;
    }
}
