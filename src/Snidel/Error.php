<?php
declare(strict_types=1);

namespace Ackintosh\Snidel;

class Error implements \ArrayAccess
{
    /** @var array */
    private $errors = [];

    /**
     * @param   mixed   $offset
     * @return  bool
     */
    public function offsetExists($offset): bool
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
    public function offsetSet($offset, $value): void
    {
        $this->errors[$offset] = $value;
    }

    /**
     * @param   mixed   $offset
     * @return  void
     */
    public function offsetUnset($offset): void
    {
        unset($this->errors[$offset]);
    }

    /**
     * @return  bool
     */
    public function exists(): bool
    {
        return count($this->errors) > 0;
    }
}
