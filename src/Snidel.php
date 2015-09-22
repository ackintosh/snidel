<?php
class Snidel
{
    /**
     * @var array
     */
    private $childPids;

    public function __construct()
    {
        $this->childPids = array();
    }

    public function fork($callable, $args = array())
    {
        $pid = pcntl_fork();
        if (-1 === $pid) {
            throw new RuntimeException('Failed to fork');
        } elseif ($pid) {
            // parent
            $this->childPids[] = $pid;
        } else {
            // child
            $ret = serialize(call_user_func_array($callable, $args));
            $data = new Snidel_Data(getmypid());
            $data->write($ret);
            exit;
        }
    }

    public function join()
    {
        foreach (range(1, count($this->childPids)) as $i) {
            pcntl_waitpid(-1, $status);
            if (!pcntl_wifexited($status)) {
                throw RuntimeException('error in child.');
            }
        }
    }

    public function get()
    {
        $ret = array();
        foreach ($this->childPids as $pid) {
            $data = new Snidel_Data($pid);
            $ret[] = unserialize($data->readAndDelete());
        }

        return $ret;
    }
}
