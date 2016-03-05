<?php
namespace Ackintosh\Snidel;

class ForkCollection implements \ArrayAccess, \Iterator
{
    /** @var \Ackintosh\Snidel\Fork[] */
    private $forks = array();

    /** @var int */
    private $position;

    /**
     * @param   \Ackintosh\Snidel\Fork[]
     */
    public function __construct($forks)
    {
        array_map(function ($fork) {
            $this->forks[] = $fork;
        }, $forks);

        $this->position = 0;
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
     * ArrayAccess interface
     *
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
     * ArrayAccess interface
     *
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
     * ArrayAccess interface
     *
     * @param   mixed   $offset
     * @return  void
     */
    public function offsetSet($offset, $value)
    {
        $this->forks[$offset] = $value;
    }

    /**
     * ArrayAccess interface
     *
     * @param   mixed   $offset
     * @return  void
     */
    public function offsetUnset($offset)
    {
        unset($this->forks[$offset]);
    }

    /**
     * Iterator interface
     *
     * @return  \Ackintosh\Snidel\Fork
     */
    public function current()
    {
        return $this->forks[$this->position];
    }

    /**
     * Iterator interface
     *
     * @return  int
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Iterator interface
     *
     * @return  void
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Iterator interface
     *
     * @return  void
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Iterator interface
     *
     * @return  bool
     */
    public function valid()
    {
        return isset($this->forks[$this->position]);
    }
}
