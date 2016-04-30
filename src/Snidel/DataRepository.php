<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Data;
use Ackintosh\Snidel\Exception\SharedMemoryControlException;

class DataRepository
{
    /**
     * @var int[]
     */
    private $pids = array();

    /**
     * load data
     *
     * @param   int             $pid
     * @return  \Ackintosh\Snidel\Data
     */
    public function load($pid)
    {
        if (!in_array($pid, $this->pids, true)) {
            $this->pids[] = $pid;
        }

        return new Data($pid);
    }

    /**
     * @return  void
     * @throws  \Ackintosh\Snidel\SharedMemoryControlException
     */
    public function deleteAll()
    {
        foreach ($this->pids as $pid) {
            $data = $this->load($pid);
            try {
                $data->deleteIfExists();
            } catch (SharedMemoryControlException $e) {
                throw $e;
            }
        }
    }
}
