<?php
namespace Ackintosh\Snidel\Result;

use Ackintosh\Snidel\Fork\Formatter as ProcessFormatter;
use Ackintosh\Snidel\Task\Formatter as TaskFormatter;

class Formatter
{
    public static function serialize(Result $result)
    {
        $cloned = clone $result;
        $serializedTask = TaskFormatter::serialize($cloned->getTask());
        $serializedProcess = ProcessFormatter::serialize($cloned->getProcess());
        $cloned->setTask(null);
        $cloned->setProcess(null);

        return serialize([
            'serializedTask'    => $serializedTask,
            'serializedProcess' => $serializedProcess,
            'result'            => $cloned,
        ]);
    }

    public static function minifyAndSerialize(Result $result)
    {
        $cloned = clone $result;

        $serializedTask = TaskFormatter::minifyAndSerialize($cloned->getTask());
        $serializedProcess = ProcessFormatter::serialize($cloned->getProcess());
        $cloned->setTask(null);
        $cloned->setProcess(null);

        return serialize([
            'serializedTask'     => $serializedTask,
            'serializedProcess'  => $serializedProcess,
            'result'             => $cloned,
        ]);
    }

    public static function unserialize($serializedResult)
    {
        $unserialized = unserialize($serializedResult);
        $unserialized['result']->setTask(TaskFormatter::unserialize($unserialized['serializedTask']));
        $unserialized['result']->setProcess(ProcessFormatter::unserialize($unserialized['serializedProcess']));

        return $unserialized['result'];
    }
}
