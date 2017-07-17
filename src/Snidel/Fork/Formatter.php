<?php
namespace Ackintosh\Snidel\Fork;

class Formatter
{
    /**
     * @param   \Ackintosh\Snidel\Fork\Process  $process
     * @return  string
     */
    public static function serialize($process)
    {
        return serialize($process);
    }

    /**
     * @param   string  $serializedProcess
     * @return  \Ackintosh\Snidel\Fork\Process
     */
    public static function unserialize($serializedProcess)
    {
        return unserialize($serializedProcess);
    }
}
