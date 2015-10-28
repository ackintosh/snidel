<?php
declare(ticks = 1);

class Snidel
{
    /**
     * @var string
     */
    const VERSION = '0.1.0';

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

    /**
     * @var array
     */
    private $results = array();

    /**
     * @var resource
     */
    private $logResource;

    /**
     * @var int
     */
    private $ownerPid;

    /**
     * @var array
     */
    private $tagsToPids = array();

    /**
     * @var array
     */
    private $signals = array(
        SIGTERM,
        SIGINT,
    );

    /**
     * @var int
     */
    private $receivedSignal;

    public function __construct($maxProcs = 5)
    {
        $this->ownerPid = getmypid();
        $this->childPids = array();
        $this->token = new Snidel_Token(getmypid(), $maxProcs);

        foreach ($this->signals as $sig) {
            pcntl_signal($sig, array($this, 'signalHandler'), false);
        }

        $this->info('parent pid: ' . $this->ownerPid);
    }

    /**
     * sets the resource for the log.
     *
     * @param   resource    $resource
     * @return  void
     */
    public function setLogResource($resource)
    {
        $this->logResource = $resource;
    }

    /**
     * fork process
     *
     * @param   callable    $callable
     * @param   array       $args
     * @param   string      $tag
     * @return  void
     * @throws  RuntimeException
     */
    public function fork($callable, $args = array(), $tag = null)
    {
        if (!is_array($args)) {
            $args = array($args);
        }

        $pid = pcntl_fork();
        if (-1 === $pid) {
            throw new RuntimeException('Failed to fork');
        } elseif ($pid) {
            // parent
            $this->info('created child process. pid: ' . $pid);
            $this->childPids[] = $pid;
            if ($tag) {
                $this->tagsToPids[$tag][] = $pid;
            }
        } else {
            // child
            foreach ($this->signals as $sig) {
                pcntl_signal($sig, SIG_DFL, true);
            }
            $this->info('waiting for the token to come around.');
            if ($this->token->accept()) {
                $this->info('started the function.');
                $ret = call_user_func_array($callable, $args);
                $this->info('completed the function.');
                $data = new Snidel_Data(getmypid());
                $data->write($ret);
                $this->token->back();
            }
            exit;
        }
    }

    /**
     * waits until all children are completed
     *
     * @return  void
     */
    public function wait()
    {
        if ($this->joined) {
            return;
        }

        $count = count($this->childPids);
        for ($i = 0; $i < $count; $i++) {
            $status = null;
            $childPid = pcntl_waitpid(-1, $status);
            if (!pcntl_wifexited($status)) {
                throw new RuntimeException('error in child.');
            }
            $data = new Snidel_Data($childPid);
            $this->results[$childPid] = $data->readAndDelete();
            unset($this->childPids[array_search($childPid, $this->childPids)]);
        }
        $this->joined = true;
    }

    /**
     * gets results
     *
     * @param   string  $tag
     * @return  array   $ret
     * @throws  RuntimeException
     */
    public function get($tag = null)
    {
        if (!$this->joined) {
            $this->wait();
        }

        if ($tag === null) {
            return array_values($this->results);
        } else {
            return $this->getWithTag($tag);
        }
    }

    /**
     * gets results with tag
     *
     * @param   string  $tag
     * @return  array   $results
     */
    private function getWithTag($tag)
    {
        $results = array();
        if (!isset($this->tagsToPids[$tag])) {
            return $results;
        }

        foreach ($this->tagsToPids[$tag] as $pid) {
            $results[] = $this->results[$pid];
        }

        return $results;
    }

    /**
     * writes log
     *
     * @param   string  $message
     * @return  void
     */
    private function info($message)
    {
        $this->writeLog('info', $message);
    }

    /**
     * writes log
     *
     * @param   string  $type
     * @param   string  $message
     * @return  void
     */
    private function writeLog($type, $message)
    {
        if ($this->logResource === null) {
            return;
        }
        $pid = getmypid();
        fputs(
            $this->logResource,
            sprintf(
                '[%s][%d(%s)] %s',
                $type,
                $pid,
                ($this->ownerPid === $pid) ? 'p' : 'c',
                $message . PHP_EOL
            )
        );
    }

    /**
     * @param   int     $sig
     * @return  void
     */
    private function signalHandler($sig)
    {
        $this->receivedSignal = $sig;
        $this->sendSignalToChild($sig);
        unset($this->token);
        exit;
    }

    /**
     * sends signal to child
     *
     * @param   int     $sig
     * @return  void
     */
    private function sendSignalToChild($sig)
    {
        foreach ($this->childPids as $pid) {
            posix_kill($pid, $sig);
        }
    }

    /**
     * delete shared memory
     *
     * @return  void
     */
    private function deleteAllData()
    {
        foreach ($this->childPids as $pid) {
            $data = new Snidel_Data($pid);
            $data->delete();
        }
    }

    public function __destruct()
    {
        if ($this->ownerPid === getmypid() && !$this->joined && $this->receivedSignal === null) {
            $this->sendSignalToChild(SIGTERM);
            $this->deleteAllData();
            unset($this->token);
            throw new RuntimeException('must be joined');
        }
    }
}
