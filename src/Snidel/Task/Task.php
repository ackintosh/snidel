<?php
namespace Ackintosh\Snidel\Task;

use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Task\TaskInterface;
use Ackintosh\Snidel\Task\MinifiedTask;

class Task implements TaskInterface
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
     * @param   string      $tag
     */
    public function __construct($callable, $args, $tag)
    {
        $this->callable     = $callable;
        $this->args         = $args;
        $this->tag          = $tag;
    }

    /**
     * @return  callable
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * @return  mixed
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @return  string|null
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @return  Ackintosh\Snidel\Result\Result
     */
    public function execute()
    {
        ob_start();
        $result = new Result();
        $result->setReturn(
            call_user_func_array(
                $this->getCallable(),
                (is_array($args = $this->getArgs())) ? $args : array($args)
            )
        );
        $result->setOutput(ob_get_clean());
        $result->setTask($this);

        return $result;
    }
}
