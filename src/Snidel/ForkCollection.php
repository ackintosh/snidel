<?php
namespace Ackintosh\Snidel;

class ForkCollection implements \ArrayAccess
{
    /** @var \Ackintosh\Snidel\Fork[] */
    private $forks = array();

    /**
     * @param   \Ackintosh\Snidel\Fork[]
     */
    public function __construct($forks)
    {
        array_map(function ($fork) {
            $this->forks[] = $fork;
        }, $forks);
    }

    /**
     * @return  array
     */
    public function toArray()
    {
        return array_map(
            function ($fork) {
                return $fork->getResult()->getReturn();
            },
            $this->forks
        );
    }

    /**
     * @param   mixed   $offset
     * @return  bool
     */
    public function offsetExists($offset)
    {
        if (isset($this->forks[$offset]) && $this->forks[$offset] !== '') {
            return true;
        }

        return false;
    }

    /**
     * @param   mixed   $offset
     * @return  mixed
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        return $this->forks[$offset];
    }

    /**
     * @param   mixed   $offset
     * @return  void
     */
    public function offsetSet($offset, $value)
    {
        $this->forks[$offset] = $value;
    }

    /**
     * @param   mixed   $offset
     * @return  void
     */
    public function offsetUnset($offset)
    {
        unset($this->forks[$offset]);
    }
}
