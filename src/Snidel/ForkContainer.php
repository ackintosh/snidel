<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Fork;
use Ackintosh\Snidel\ForkCollection;
use Ackintosh\Snidel\Pcntl;
use Ackintosh\Snidel\DataRepository;
use Ackintosh\Snidel\Exception\SharedMemoryControlException;

class ForkContainer
{
    /** @var \Ackintosh\Snidel\Fork[] */
    private $forks = array();

    /** @var \Ackintosh\Snidel\Pcntl */
    private $pcntl;

    /** @var \Ackintosh\Snidel\DataRepository */
    private $dataRepository;

    public function __construct()
    {
        $this->pcntl            = new Pcntl();
        $this->dataRepository   = new DataRepository();
    }

    /**
     * fork process
     *
     * @param   \Ackintosh\Snidel\Task
     * @return  \Ackintosh\Snidel\Fork
     * @throws  \RuntimeException
     */
    public function fork($task)
    {
        $pid = $this->pcntl->fork();
        if ($pid === -1) {
            throw new \RuntimeException('could not fork a new process');
        }

        $pid = ($pid === 0) ? getmypid() : $pid;

        $fork = new Fork($pid);
        $fork->setTask($task);

        return $fork;
    }

    /**
     *
     * @param   string  $tag
     * @return  bool
     */
    public function hasTag($tag)
    {
        foreach ($this->forks as $fork) {
            if ($fork->getTag() === $tag) {
                return true;
            }
        }
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
        try {
            $this->forks[$childPid] = $this->dataRepository->load($childPid)->readAndDelete();
        } catch (SharedMemoryControlException $e) {
            throw $e;
        }
        $this->forks[$childPid]->setStatus($status);

        return $this->forks[$childPid];
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
