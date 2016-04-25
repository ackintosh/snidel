<?php
namespace Ackintosh\Snidel;
use Opis\Closure\SerializableClosure;

class Task
{
    /** @var callable */
    private $callable;

    /** @var string */
    private $serializedCallable;

    /** @var array */
    private $args;

    /** @var string */
    private $tag;

    /**
     * @param   callable    $callable
     * @param   array       $args
     * @param   string      $string
     */
    public function __construct($callable, $args, $tag)
    {
        $this->callable     = $callable;
        $this->args         = $args;
        $this->tag          = $tag;
    }

    /**
     * @param   \Ackintosh\Snidel\Task      $task
     * @return  string
     */
    public static function serialize($task)
    {
        $task->serializeCallable();

        return serialize($task);
    }

    /**
     * @return  void
     */
    private function serializeCallable()
    {
        $this->callable = $this->isClosure($this->callable) ? new SerializableClosure($this->callable) : $this->callable;
    }

    /**
     * @param   string  $serializedTask
     * @return  \Ackintosh\Snidel\Task
     */
    public static function unserialize($serializedTask)
    {
        $task = unserialize($serializedTask);
        $task->unserializeCallable();

        return $task;
    }

    /**
     * @return  \Ackintosh\Snidel\Task
     */
    private function unserializeCallable()
    {
        if ($this->isClosure($this->callable)) {
            $this->callable = $this->callable->getClosure();
        }
    }

    /**
     * @return  callable
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * @return  array
     */
    public function getArgs()
    {
        return is_array($this->args) ? $this->args : array($this->args);
    }

    /**
     * @return  string|null
     */
    public function getTag()
    {
        return $this->tag;
    }

    private function isClosure()
    {
        return is_object($this->callable) && is_callable($this->callable);
    }
}
