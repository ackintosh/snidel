<?php
namespace Ackintosh\Snidel\Result;

class Result
{
    /** @var mixed */
    private $return;

    /** @var string */
    private $output;

    /** @var \Ackintosh\Snidel\Fork\Process */
    private $process;

    /** @var \Ackintosh\Snidel\Task\Task */
    private $task;

    /** @var bool */
    private $failure = false;

    /** @var array */
    private $error;

    /**
     * set return
     *
     * @param   mixed     $return
     * @return  void
     */
    public function setReturn($return)
    {
        $this->return = $return;
    }

    /**
     * return return value
     *
     * @return  mixed
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
     * @param   \Ackintosh\Snidel\Fork\Process
     * @return  void
     */
    public function setProcess($fork)
    {
        $this->process = $fork;
    }

    /**
     * @return  \Ackintosh\Snidel\Fork\Process
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @param   \Ackintosh\Snidel\Task\Task
     * @return  void
     */
    public function setTask($task)
    {
        $this->task = $task;
    }

    /**
     * @return  \Ackintosh\Snidel\Task\Task
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * @param   array | null
     * @return  void
     */
    public function setError($error)
    {
        $this->failure  = true;
        $this->error    = $error;
    }

    /**
     * @return  array | null
     */
    public function getError()
    {
        return $this->error;
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
