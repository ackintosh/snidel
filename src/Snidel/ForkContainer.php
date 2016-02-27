<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Fork;
use Ackintosh\Snidel\Pcntl;

class ForkContainer implements \ArrayAccess
{
    /** @var \Ackintosh\Snidel\Fork[] */
    private $forks = array();

    /** @var \Ackintosh\Snidel\Pcntl */
    private $pcntl;

    /** @var array */
    private $tagsToPids = array();

    public function __construct()
    {
        $this->pcntl = new Pcntl();
    }

    /**
     * fork process
     *
     * @return \Ackintosh\Snidel\Fork
     * @throws \RuntimeException
     */
    public function fork($tag = null)
    {
        $pid = $this->pcntl->fork();
        if ($pid === -1) {
            throw new \RuntimeException('could not fork a new process');
        }

        $this->forks[$pid] = new Fork($pid);
        if ($tag !== null) {
            $this->tagsToPids[$pid] = $tag;
        }

        return $this->forks[$pid];
    }

    /**
     *
     * @param   string  $tag
     * @return  bool
     */
    public function hasTag($tag)
    {
        return in_array($tag, $this->tagsToPids, true);
    }

    /**
     * wait child
     *
     * @return \Ackintosh\Snidel\Fork
     */
    public function wait()
    {
        $status = null;
        $childPid = $this->pcntl->waitpid(-1, $status);
        $this[$childPid]->setStatus($status);
        $this[$childPid]->loadResult();

        return $this[$childPid];
    }

    public function get($tag = null)
    {
        if ($tag === null) {
            return $this->forks;
        }

        return $this->getWithTag($tag);
    }

    /**
     * return forks
     *
     * @param   string  $tag
     * @return  \Ackintosh\Snidel\Fork[]
     */
    private function getWithTag($tag)
    {
        return array_filter($this->forks, function ($fork) use ($tag) {
            return $this->tagsToPids[$fork->getPid()] === $tag;
        });
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
