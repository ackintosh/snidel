<?php
declare(ticks = 1);

class Snidel
{
    /** @var string */
    const VERSION = '0.1.0';

    /** @var array */
    private $childPids;

    /** @var int */
    private $maxProcs;

    /** @var Snidel_Token */
    private $token;

    /** @var bool */
    private $joined = false;

    /** @var array */
    private $results = array();

    /** @var resource */
    private $logResource;

    /** @var int */
    private $ownerPid;

    /** @var array */
    private $tagsToPids = array();

    /** @var array */
    private $signals = array(
        SIGTERM,
        SIGINT,
    );

    /** @var int */
    private $receivedSignal;

    public function __construct($maxProcs = 5)
    {
        $this->ownerPid     = getmypid();
        $this->childPids    = array();
        $this->maxProcs     = $maxProcs;
        $this->token        = new Snidel_Token(getmypid(), $maxProcs);

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
     * @return  int         $pid        forked PID of forked child process
     * @throws  RuntimeException
     */
    public function fork($callable, $args = array(), $tag = null, Snidel_Token $token = null)
    {
        $token = $token ? $token : $this->token;
        if (!is_array($args)) {
            $args = array($args);
        }

        $pid = pcntl_fork();
        if (-1 === $pid) {
            $message = 'could not fork a new process';
            $this->error($message);
            throw new RuntimeException($message);
        } elseif ($pid) {
            // parent
            $this->info('created child process. pid: ' . $pid);
            $this->childPids[] = $pid;
            if ($tag !== null) {
                $this->tagsToPids[$tag][] = $pid;
            }
        } else {
            // child
            foreach ($this->signals as $sig) {
                pcntl_signal($sig, SIG_DFL, true);
            }
            $this->info('waiting for the token to come around.');
            if ($token->accept()) {
                $this->info('started the function.');
                $ret = call_user_func_array($callable, $args);
                $this->info('completed the function.');
                $data = new Snidel_Data(getmypid());
                try {
                    $data->write($ret);
                } catch (RuntimeException $e) {
                    throw $e;
                }
                $token->back();
            }
            $this->_exit();
        }

        return $pid;
    }

    /**
     * waits until all children are completed
     *
     * @return  void
     * @throws  RuntimeException
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
                $message = 'error in child.';
                $this->error($message);
                throw new RuntimeException($message);
            }
            $data = new Snidel_Data($childPid);
            try {
                $this->results[$childPid] = $data->readAndDelete();
            } catch (RuntimeException $e) {
                throw $e;
            }
            unset($this->childPids[array_search($childPid, $this->childPids)]);
        }
        $this->joined = true;
    }

    /**
     * gets results
     *
     * @param   string  $tag
     * @return  array   $ret
     * @throws  InvalidArgumentException
     */
    public function get($tag = null)
    {
        if (!$this->joined) {
            $this->wait();
        }

        if ($tag === null) {
            return array_values($this->results);
        } else {
            try {
                return $this->getWithTag($tag);
            } catch (InvalidArgumentException $e) {
                throw $e;
            }
        }
    }

    /**
     * gets results with tag
     *
     * @param   string  $tag
     * @return  array   $results
     * @throws  InvalidArgumentException
     */
    private function getWithTag($tag)
    {
        if (!isset($this->tagsToPids[$tag])) {
            throw new InvalidArgumentException('There is no tags: ' . $tag);
        }

        $results = array();
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
     * @param   string  $message
     * @return  void
     */
    private function error($message)
    {
        $this->writeLog('error', $message);
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
                '[%s][%s][%d(%s)] %s',
                date('Y-m-d H:i:s'),
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
        $this->_exit();
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
     * @throws  RuntimeException
     */
    private function deleteAllData()
    {
        foreach ($this->childPids as $pid) {
            $data = new Snidel_Data($pid);
            try {
                $data->delete();
            } catch (RuntimeException $e) {
                throw $e;
            }
        }
    }

    /**
     * create map object
     *
     * @param   array       $args
     * @param   callable    $callable
     * @return  void
     */
    public function map(Array $args, $callable)
    {
        return new Snidel_MapContainer($args, $callable, $this->maxProcs);
    }

    /**
     * run map object
     *
     * @param   Snidel_MapContainer
     * @return  array
     * @throws  RuntimeException
     */
    public function run(Snidel_MapContainer $mapContainer)
    {
        try {
            $this->forkTheFirstProcessing($mapContainer);
            $this->waitsAndConnectsProcess($mapContainer);
        } catch (RuntimeException $e) {
            throw $e;
        }

        return $this->getResultsOf($mapContainer);
    }

    /**
     * fork the first processing of the map container
     *
     * @param   Snidel_MapContainer
     * @return  void
     * @throws  RuntimeException
     */
    private function forkTheFirstProcessing(Snidel_MapContainer $mapContainer)
    {
        foreach ($mapContainer->getFirstArgs() as $args) {
            try {
                $childPid = $this->fork($mapContainer->getFirstMap()->getCallable(), $args);
            } catch (RuntimeException $e) {
                throw $e;
            }
            $mapContainer->getFirstMap()->countTheForked();
            $mapContainer->getFirstMap()->addChildPid($childPid);
        }
    }

    /**
     * waits and connects the process of map container
     *
     * @param   Snidel_MapContainer
     * @return  void
     * @throws  RuntimeException
     */
    private function waitsAndConnectsProcess(Snidel_MapContainer $mapContainer)
    {
        if ($this->joined) {
            return;
        }

        while ($mapContainer->isProcessing()) {
            $status = null;
            $childPid = pcntl_waitpid(-1, $status);
            if (!pcntl_wifexited($status)) {
                $message = 'error in child.';
                $this->error($message);
                throw new RuntimeException($message);
            }
            $data = new Snidel_Data($childPid);
            try {
                $this->results[$childPid] = $data->readAndDelete();
            } catch (RuntimeException $e) {
                throw $e;
            }
            unset($this->childPids[array_search($childPid, $this->childPids)]);
            if ($nextMap = $mapContainer->nextMap($childPid)) {
                try {
                    $nextMapPid = $this->fork(
                        $nextMap->getCallable(),
                        array($this->results[$childPid]),
                        null,
                        $nextMap->getToken()
                    );
                } catch (RuntimeException $e) {
                    throw $e;
                }
                $this->info('started next map ' . $childPid . ' -> ' . $nextMapPid);
                $nextMap->countTheForked();
                $nextMap->addChildPid($nextMapPid);
            }
            $mapContainer->countTheCompleted($childPid);
        }

        $this->joined = true;
    }

    /**
     * gets results of map container
     *
     * @param   Snidel_MapContainer
     * @return  array
     */
    private function getResultsOf(Snidel_MapContainer $mapContainer)
    {
        $results = array();
        foreach ($mapContainer->getLastMapPids() as $pid) {
            $results[] = $this->results[$pid];
        }

        return $results;
    }

    private function _exit($status = 0)
    {
        exit($status);
    }

    public function __destruct()
    {
        if ($this->ownerPid === getmypid() && !$this->joined && $this->receivedSignal === null) {
            $this->sendSignalToChild(SIGTERM);
            $this->deleteAllData();
            unset($this->token);

            $message = 'must be joined';
            $this->error($message);
            throw new RuntimeException($message);
        }
    }
}
