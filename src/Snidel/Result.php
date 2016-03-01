<?php
namespace Ackintosh\Snidel;

class Result
{
    /** @var mix */
    private $return;

    /** @var string */
    private $output;

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
}
