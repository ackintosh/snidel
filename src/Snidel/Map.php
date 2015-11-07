<?php
class Snidel_Map
{
    /** @var Snidel_Token */
    private $token;

    /** @var callable */
    private $callable;

    /** @var array */
    private $childPids = array();

    /** @var int */
    private $forkedCount = 0;

    /** @var int */
    private $completedCount = 0;

    public function __construct($callable, $maxProcs)
    {
        $this->token = new Snidel_Token(getmypid(), $maxProcs, (string)mt_rand(1, 10000));
        $this->callable = $callable;
    }

    public function getCallable()
    {
        return $this->callable;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function addChildPid($childPid)
    {
        $this->childPids[] = $childPid;
    }

    public function getChildPids()
    {
        return $this->childPids;
    }

    public function hasChild($childPid)
    {
        return in_array($childPid, $this->childPids, true);
    }

    public function countTheForked()
    {
        $this->forkedCount++;
    }

    public function countTheCompleted()
    {
        $this->completedCount++;
    }

    public function isProcessing()
    {
        if ($this->forkedCount === 0 || $this->completedCount === 0) {
            return true;
        }

        if ($this->forkedCount === $this->completedCount) {
            return false;
        }

        return true;
    }
}
