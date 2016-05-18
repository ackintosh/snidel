<?php
namespace Ackintosh\Snidel\Fork;

class Formatter
{
    /**
     * @param   \Ackintosh\Snidel\Fork\Fork  $fork
     * @return  string
     */
    public static function serialize($fork)
    {
        return serialize($fork);
    }

    /**
     * @param   string  $serializedFork
     * @return  \Ackintosh\Snidel\Fork\Fork
     */
    public static function unserialize($serializedFork)
    {
        return unserialize($serializedFork);
    }
}
