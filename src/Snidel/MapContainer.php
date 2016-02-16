<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Map;
use Ackintosh\Snidel\Exception\MapContainerException;

class MapContainer
{
    /** @var array */
    private $args;

    /** @var Snidel\Map[] */
    private $maps = array();

    /** @var int */
    private $concurrency;

    /**
     * @param   array       $args
     * @param   callable    $callable
     * @param   int         $concurrency
     */
    public function __construct(Array $args, $callable, $concurrency)
    {
        $this->args = $args;
        $this->maps[] = new Map($callable, $concurrency);
        $this->concurrency = $concurrency;
    }

    /**
     * stacks map object
     *
     * @param   callable                $callable
     * @return  Snidel\MapContainer     $this
     */
    public function then($callable)
    {
        $this->maps[] = new Map($callable, $this->concurrency);
        return $this;
    }

    /**
     * returns first map
     *
     * @return Snidel\Map
     */
    public function getFirstMap()
    {
        return $this->maps[0];
    }

    /**
     * returns args
     *
     * @return array
     */
    public function getFirstArgs()
    {
        return $this->args;
    }

    /**
     * returns array of PID last map owned
     *
     * @return array
     */
    public function getLastMapPids()
    {
        return $this->maps[(count($this->maps) - 1)]->getChildPids();
    }

    /**
     * maps are at that time processing its function or not
     *
     * @return bool
     */
    public function isProcessing()
    {
        foreach ($this->maps as $m) {
            if ($m->isProcessing()) {
                return true;
            }
        }

        return false;
    }

    /**
     * count up the number of completed
     *
     * @param   int     $childPid
     * @return  void
     */
    public function countTheCompleted($childPid)
    {
        foreach ($this->maps as $m) {
            if (!$m->hasChild($childPid)) {
                continue;
            }
            $m->countTheCompleted();
        }
    }

    /**
     * returns next map
     *
     * @param   int     $childPid
     * @return  Snidel\Map
     */
    public function nextMap($childPid)
    {
        try {
            $currentIndex = $this->getMapIndex($childPid);
        } catch (MapContainerException $e) {
            throw $e;
        }
        if (isset($this->maps[$currentIndex + 1])) {
            return $this->maps[$currentIndex + 1];
        }

        return;
    }

    /**
     * returns array index of map that owns $childPid
     *
     * @param   int     $childPid
     * @return  int
     */
    private function getMapIndex($childPid)
    {
        foreach ($this->maps as $index => $m) {
            if (!$m->hasChild($childPid)) {
                continue;
            }

            return $index;
        }

        throw new MapContainerException('childPid not found. pid: ' . $childPid);
    }
}
