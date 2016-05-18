<?php
namespace Ackintosh\Snidel;

class ForkFormatter
{
    /**
     * @param   \Ackintosh\Snidel\Fork  $fork
     * @return  string
     */
    public static function serialize($fork)
    {
        return serialize($fork);
    }

    /**
     * @param   string  $serializedFork
     * @return  \Ackintosh\Snidel\Fork
     */
    public static function unserialize($serializedFork)
    {
        return unserialize($serializedFork);
    }
}
