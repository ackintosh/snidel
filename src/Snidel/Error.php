<?php
namespace Ackintosh\Snidel;

class Error implements \ArrayAccess
{
    /** @var array */
    private $errors = [];

    /**
     * @param   mixed   $offset
     * @return  bool
     */
    public function offsetExists($offset)
    {
        if (isset($this->errors[$offset]) && $this->errors[$offset] !== '') {
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

        return $this->errors[$offset];
    }

    /**
     * @param   mixed   $offset
     * @return  void
     */
    public function offsetSet($offset, $value)
    {
        $this->errors[$offset] = $value;
    }

    /**
     * @param   mixed   $offset
     * @return  void
     */
    public function offsetUnset($offset)
    {
        unset($this->errors[$offset]);
    }

    /**
     * @return  bool
     */
    public function exists()
    {
        return count($this->errors) > 0;
    }
}
