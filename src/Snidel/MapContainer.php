<?php
class Snidel_MapContainer
{
    /** @var array */
    private $args;

    /** @var Snidel_Map[] */
    private $maps = array();

    public function __construct($args, $func)
    {
        $this->args = $args;
        $this->maps[] = new Snidel_Map($func);
    }

    public function then($func)
    {
        $this->maps[] = new Snidel_Map($func);
        return $this;
    }

    public function getFirstMap()
    {
        return $this->maps[0];
    }

    public function getFirstArgs()
    {
        return $this->args;
    }

    public function getLastMapPids()
    {
        return $this->maps[(count($this->maps) - 1)]->getChildPids();
    }

    public function isProcessing()
    {
        foreach ($this->maps as $m) {
            if ($m->isProcessing()) {
                return true;
            }
        }

        return false;
    }

    public function countTheCompleted($childPid)
    {
        foreach ($this->maps as $m) {
            if (!$m->hasChild($childPid)) {
                continue;
            }
            $m->countTheCompleted();
        }
    }

    public function nextMap($childPid)
    {
        $currentIndex = $this->getMapIndex($childPid);
        if (isset($this->maps[$currentIndex + 1])) {
            return $this->maps[$currentIndex + 1];
        }

        return;
    }

    private function getMapIndex($childPid)
    {
        foreach ($this->maps as $index => $m) {
            if (!$m->hasChild($childPid)) {
                continue;
            }

            return $index;
        }

        return;
    }
}
