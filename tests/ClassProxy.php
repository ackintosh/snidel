<?php

class ClassProxy
{
    /** @var mixed */
    private $instance;

    public static function on($instance)
    {
        return new self($instance);
    }

    public function __construct($instance)
    {
        $this->instance = $instance;
    }

    public function __get($name)
    {
        $prop = new \ReflectionProperty($this->instance, $name);
        $prop->setAccessible(true);
        return $prop->getValue($this->instance);
    }

    public function __set($name, $value)
    {
        $prop = new \ReflectionProperty($this->instance, $name);
        $prop->setAccessible(true);
        $prop->setValue($this->instance, $value);
    }

    public function __call($name, $arguments)
    {
        $method = new \ReflectionMethod($this->instance, $name);
        $method->setAccessible(true);

        return $method->invokeArgs($this->instance, $arguments);
    }
}
