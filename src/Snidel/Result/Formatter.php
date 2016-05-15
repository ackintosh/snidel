<?php
namespace Ackintosh\Snidel\Result;

use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Fork;

class Formatter
{
    public static function serialize(Result $result)
    {
        $cloned = clone $result;
        $serializedFork = Fork::serialize($cloned->getFork());
        $cloned->setFork(null);

        return serialize(array('serializedFork' => $serializedFork, 'result' => $cloned));
    }

    public static function minifyAndSerialize(Result $result)
    {
        $cloned = clone $result;
        $serializedFork = Fork::minifyAndSerialize($cloned->getFork());
        $cloned->setFork(null);

        return serialize(array('serializedFork' => $serializedFork, 'result' => $cloned));
    }

    public static function unserialize($serializedResult)
    {
        $unserialized = unserialize($serializedResult);
        $unserialized['result']->setFork(Fork::unserialize($unserialized['serializedFork']));

        return $unserialized['result'];
    }
}
