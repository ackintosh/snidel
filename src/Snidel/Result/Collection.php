<?php
namespace Ackintosh\Snidel\Result;

class Collection implements \ArrayAccess, \Iterator
{
    /** @var \Ackintosh\Snidel\Result\Result[] */
    private $results = [];

    /** @var int */
    private $position;

    /**
     * @param   \Ackintosh\Snidel\Result\Result[]
     */
    public function __construct($results)
    {
        foreach ($results as $f) {
            $this->results[] = $f;
        }
        $this->position = 0;
    }

    /**
     * @return  array
     */
    public function toArray()
    {
        return array_map(
            function ($result) {
                return $result->getReturn();
            },
            $this->results
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
        if (isset($this->results[$offset]) && $this->results[$offset] !== '') {
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

        return $this->results[$offset];
    }

    /**
     * ArrayAccess interface
     *
     * @param   mixed   $offset
     * @return  void
     */
    public function offsetSet($offset, $value)
    {
        $this->results[$offset] = $value;
    }

    /**
     * ArrayAccess interface
     *
     * @param   mixed   $offset
     * @return  void
     */
    public function offsetUnset($offset)
    {
        unset($this->results[$offset]);
    }

    /**
     * Iterator interface
     *
     * @return  \Ackintosh\Snidel\Fork\Fork
     */
    public function current()
    {
        return $this->results[$this->position];
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
        return isset($this->results[$this->position]);
    }
}
