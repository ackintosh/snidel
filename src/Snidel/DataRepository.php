<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Data;

class DataRepository
{
    /**
     * load data
     *
     * @param   int             $pid
     * @return  Snidel\Data
     */
    public function load($pid)
    {
        return new Data($pid);
    }
}
