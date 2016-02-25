<?php
namespace Ackintosh\Snidel;

class Result
{
    /** @var mix */
    private $return;

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
}
