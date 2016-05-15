<?php
namespace Ackintosh\Snidel\Result;

class Result
{
    /** @var mix */
    private $return;

    /** @var string */
    private $output;

    private $fork;

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

    public function setFailure()
    {
        $this->failure = true;
    }

    public function setFork($fork)
    {
        $this->fork = $fork;
    }

    public function getFork()
    {
        return $this->fork;
    }

    public function isFailure()
    {
        return $this->failure;
    }
}
