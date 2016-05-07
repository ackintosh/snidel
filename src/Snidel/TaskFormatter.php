<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\TaskInterface;
use Ackintosh\Snidel\Task;
use Ackintosh\Snidel\MinifiedTask;
use Opis\Closure\SerializableClosure;

class TaskFormatter
{
    /**
     * @param   Ackintosh\Snidel\TaskInterface  $task
     * @return  string
     */
    public static function serialize(TaskInterface $task)
    {
        $callable = $task->getCallable();
        
        return serialize(new Task(
            (self::isClosure($callable) ? new SerializableClosure($callable) : $callable),
            $task->getArgs(),
            $task->getTag()
        ));
    }

    /**
     * @param   Ackintosh\Snidel\TaskInterface  $task
     * @return  string
     */
    public static function minifyAndSerialize(TaskInterface $task)
    {
        return serialize(self::minify($task));
    }

    /**
     * @param   Ackintosh\Snidel\TaskInterface  $task
     * @return  Ackintosh\Snidel\MinifiedTask
     */
    private static function minify(TaskInterface $task)
    {
        return new MinifiedTask(
            $task->getTag()
        );
    }

    /**
     * @param   string  $serializedTask
     * @return  \Ackintosh\Snidel\TaskInterface
     */
    public static function unserialize($serializedTask)
    {
        $task = unserialize($serializedTask);
        if (self::isClosure($callable = $task->getCallable())) {
            $task = new Task(
                $callable->getClosure(),
                $task->getArgs(),
                $task->getTag()
            );
        }

        return $task;
    }

    /**
     * @param   mixed   $callable
     * @return  bool
     */
    private static function isClosure($callable)
    {
        return is_object($callable) && is_callable($callable);
    }
}
