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

    /**
     * @param   callable    $callable
     * @param   int         $maxProcs
     */
    public function __construct($callable, $maxProcs)
    {
        $this->token = new Snidel_Token(getmypid(), $maxProcs);
        $this->callable = $callable;
    }

    /**
     * returns callable
     *
     * @return  callable
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * returns token
     *
     * @return  Snidel_Token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * stacks child Pid
     *
     * @param   int     $childPid
     * @return  void
     */
    public function addChildPid($childPid)
    {
        $this->childPids[] = $childPid;
    }

    /**
     * returns array of child pid
     *
     * @return  int[]
     */
    public function getChildPids()
    {
        return $this->childPids;
    }

    /**
     * has child pid or not
     *
     * @param   int     $childPid
     * @return  bool
     */
    public function hasChild($childPid)
    {
        return in_array($childPid, $this->childPids, true);
    }

    /**
     * count up the number of forked
     *
     * @return  void
     */
    public function countTheForked()
    {
        $this->forkedCount++;
    }

    /**
     * count up the number of completed
     *
     * @return  void
     */
    public function countTheCompleted()
    {
        $this->completedCount++;
    }

    /**
     * at that time processing its function or not
     *
     * @return bool
     */
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
