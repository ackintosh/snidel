<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Fork;
use Ackintosh\Snidel\ForkCollection;
use Ackintosh\Snidel\Pcntl;

class ForkContainer
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

        $pid = ($pid === 0) ? getmypid() : $pid;

        $this->forks[$pid] = new Fork($pid);
        $this->forks[$pid]->setTag($tag);
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
        $this->get($childPid)->setStatus($status);
        $this->get($childPid)->loadResult();

        return $this->get($childPid);
    }

    /**
     * return fork
     *
     * @param   int     $pid
     * @return  \Ackintosh\Snidel\Fork
     */
    public function get($pid)
    {
        return $this->forks[$pid];
    }

    public function getCollection($tag = null)
    {
        if ($tag === null) {
            return new ForkCollection($this->forks);
        }

        return $this->getCollectionWithTag($tag);
    }

    /**
     * return forks
     *
     * @param   string  $tag
     * @return  \Ackintosh\Snidel\Fork[]
     */
    private function getCollectionWithTag($tag)
    {
        $collection = array_filter($this->forks, function ($fork) use ($tag) {
            return $fork->getTag() ===  $tag;
        });

        return new ForkCollection($collection);
    }
}
