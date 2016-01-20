<?php
class Snidel_DataRepository
{
    /**
     * load data
     *
     * @param   int             $pid
     * @return  Snidel_Data
     */
    public function load($pid)
    {
        return new Snidel_Data($pid);
    }
}
