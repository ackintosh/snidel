<?php
namespace Ackintosh\Snidel\Result;

class Result
{
    /** @var mix */
    private $return;

    /** @var string */
    private $output;

    /** @var Ackintosh\Snidel\Fork */
    private $fork;

    /** @var Ackintosh\Snidel\Task\Task */
    private $task;

    /** @var bool */
    private $failure = false;

    /**
     * set return
     *
     * @param   mix     $return
     * @return  void
     */
    public function setReturn($return)
    {
        $this->return = $return;
    }

    /**
     * return return value
     *
     * @return  mix
     */
    public function getReturn()
    {
        return $this->return;
    }

    /**
     * set output
     *
     * @param   string  $output
     * @return  void
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * return output
     *
     * @return  string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return  void
     */
    public function setFailure()
    {
        $this->failure = true;
    }

    /**
     * @param   Ackintosh\Snidel\Fork
     * @return  void
     */
    public function setFork($fork)
    {
        $this->fork = $fork;
    }

    /**
     * @return  Ackintosh\Snidel\Fork
     */
    public function getFork()
    {
        return $this->fork;
    }

    /**
     * @param   Ackintosh\Snidel\Task\Task
     * @return  void
     */
    public function setTask($task)
    {
        $this->task = $task;
    }

    /**
     * @return  Ackintosh\Snidel\Task\Task
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * @return  bool
     */
    public function isFailure()
    {
        return $this->failure;
    }

    public function __clone()
    {
        // to avoid point to same object.
        $this->task = clone $this->task;
    }
}
