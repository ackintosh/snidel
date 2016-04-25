<?php
namespace Ackintosh\Snidel;
use Opis\Closure\SerializableClosure;

class Task
{
    /** @var callable */
    private $callable;

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
     * @return  string
     */
    public function serialize()
    {
        $serializedCallable = $this->isClosure($this->callable) ? new SerializableClosure($this->callable) : serialize($this->callable);
        return serialize(new self($serializedCallable, $this->args, $this->tag));
    }

    /**
     * @return  \Ackintosh\Snidel\Task
     */
    public function unserialize()
    {
        $unserializedCallable = $this->isClosure($this->callable) ? $this->callable->getClosure() : unserialize($this->callable);
        return new self($unserializedCallable, $this->args, $this->tag);
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
