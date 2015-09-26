<?php
class Snidel
{
    /**
     * @var array
     */
    private $childPids;

    /**
     * @var Snidel_Token
     */
    private $token;

    /**
     * @var bool
     */
    private $joined = false;

    public function __construct($maxProcs = 5)
    {
        $this->ownerPid = getmypid();
        $this->childPids = array();
        $this->token = new Snidel_Token(getmypid(), $maxProcs);
    }

    public function fork($callable, $args = array())
    {
        if (!is_array($args)) {
            $args = array($args);
        }

        $pid = pcntl_fork();
        if (-1 === $pid) {
            throw new RuntimeException('Failed to fork');
        } elseif ($pid) {
            // parent
            $this->childPids[] = $pid;
        } else {
            // child
            if ($this->token->accept()) {
                $childPid = getmypid();
                $ret = serialize(call_user_func_array($callable, $args));
                $data = new Snidel_Data($childPid);
                $data->write($ret);
                $this->token->back();
            }
            exit;
        }
    }

    public function join()
    {
        foreach (range(1, count($this->childPids)) as $i) {
            pcntl_waitpid(-1, $status);
            if (!pcntl_wifexited($status)) {
                throw new RuntimeException('error in child.');
            }
        }

        $this->joined = true;
    }

    public function get()
    {
        if ($this->joined === false) {
            $this->join();
        }

        $ret = array();
        foreach ($this->childPids as $pid) {
            $data = new Snidel_Data($pid);
            $ret[] = unserialize($data->readAndDelete());
        }

        return $ret;
    }

    public function __destruct()
    {
        if ($this->ownerPid === getmypid() && $this->joined === false) {
            throw new RuntimeException('must be joined');
        }
    }
}
