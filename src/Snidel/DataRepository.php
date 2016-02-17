<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Data;

class DataRepository
{
    /**
     * load data
     *
     * @param   int             $pid
     * @return  \Ackintosh\Snidel\Data
     */
    public function load($pid)
    {
        return new Data($pid);
    }
}
