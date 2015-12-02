<?php
declare(ticks = 1);

class Snidel
{
    /** @var string */
    const VERSION = '0.2.0';

    /** @var array */
    private $childPids = array();

    /** @var array */
    private $errors;

    /** @var int */
    private $concurrency;

    /** @var Snidel_Token */
    private $token;

    /** @var Snidel_Log */
    private $log;

    /** @var bool */
    private $joined = false;

    /** @var array */
    private $results = array();

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

    /** @var Snidel_Token */
    private $processToken;

    /** @var array */
    private $processInformation = array();

    public function __construct($concurrency = 5)
    {
        $this->ownerPid     = getmypid();
        $this->childPids    = array();
        $this->concurrency  = $concurrency;
        $this->token        = new Snidel_Token(getmypid(), $concurrency);
        $this->log          = new Snidel_Log(getmypid());

        foreach ($this->signals as $sig) {
            pcntl_signal($sig, array($this, 'signalHandler'), false);
        }

        $this->log->info('parent pid: ' . $this->ownerPid);
    }

    /**
     * sets the resource for the log.
     *
     * @param   resource    $resource
     * @return  void
     * @codeCoverageIgnore
     */
    public function setLoggingDestination($resource)
    {
        $this->log->setDestination($resource);
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
        $this->processToken = $token ? $token : $this->token;
        if (!is_array($args)) {
            $args = array($args);
        }

        $pid = pcntl_fork();
        if (-1 === $pid) {
            $message = 'could not fork a new process';
            $this->log->error($message);
            throw new RuntimeException($message);
        } elseif ($pid) {
            // parent
            $this->log->info('created child process. pid: ' . $pid);
            $this->childPids[] = $pid;
            if ($tag !== null) {
                $this->tagsToPids[$tag][] = $pid;
            }
        } else {
            // child
            register_shutdown_function(array($this, 'childShutdownFunction'));
            $this->processInformation['callable']    = $callable instanceof Closure ? '*Closure*' : $callable;
            $this->processInformation['args']        = $args;

            foreach ($this->signals as $sig) {
                pcntl_signal($sig, SIG_DFL, true);
            }
            $this->log->info('--> waiting for the token come around.');
            if ($this->processToken->accept()) {
                $this->log->info('----> started the function.');
                $this->processInformation['return'] = call_user_func_array($callable, $args);
                $this->log->info('<---- completed the function.');
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
            $data = new Snidel_Data($childPid);
            try {
                $result = $data->readAndDelete();
            } catch (RuntimeException $e) {
                throw $e;
            }

            if (!pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
                $message = 'an error has occurred in child process. pid: ' . $childPid;
                $this->log->error($message);
                $this->errors[$childPid] = array(
                    'status'    => $status,
                    'message'   => $message,
                    'callable'  => $result['callable'],
                    'args'      => $result['args'],
                    'return'    => isset($result['return']) ? $result['return'] : null,
                );
            } else {
                $this->results[$childPid] = $result['return'];
            }
            unset($this->childPids[array_search($childPid, $this->childPids)]);
        }
        $this->joined = true;
    }

    public function getErrors()
    {
        return $this->errors;
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
            throw new InvalidArgumentException('unknown tag: ' . $tag);
        }

        $results = array();
        foreach ($this->tagsToPids[$tag] as $pid) {
            $results[] = $this->results[$pid];
        }

        return $results;
    }

    /**
     * @param   int     $sig
     * @return  void
     */
    private function signalHandler($sig)
    {
        $this->log->info('received signal. signo: ' . $sig);
        $this->receivedSignal = $sig;

        $this->log->info('--> sending a signal to children.');
        $this->sendSignalToChildren($sig);

        $this->log->info('--> deleting token.');
        unset($this->token);

        $this->log->info('<-- signal handling has been completed successfully.');
        $this->_exit();
    }

    /**
     * sends signal to child
     *
     * @param   int     $sig
     * @return  void
     */
    private function sendSignalToChildren($sig)
    {
        foreach ($this->childPids as $pid) {
            $this->log->info('----> sending a signal to child. pid: ' . $pid);
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
     * @return  Snidel_MapContainer
     */
    public function map(Array $args, $callable)
    {
        return new Snidel_MapContainer($args, $callable, $this->concurrency);
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
            $data = new Snidel_Data($childPid);
            try {
                $result = $data->readAndDelete();
            } catch (RuntimeException $e) {
                throw $e;
            }

            if (!pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
                $message = 'an error has occurred in child process. pid: ' . $childPid;
                $this->log->error($message);
                throw new RuntimeException($message);
            } else {
                $this->results[$childPid] = $result['return'];
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
                $message = sprintf('processing is connected from [%d] to [%d]', $childPid, $nextMapPid);
                $this->log->info($message);
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

    public function childShutdownFunction()
    {
        $data = new Snidel_Data(getmypid());
        try {
            $data->write($this->processInformation);
        } catch (RuntimeException $e) {
            throw $e;
        }
        $this->log->info('<-- return token.');
        $this->processToken->back();
    }

    public function __destruct()
    {
        if ($this->ownerPid === getmypid() && !$this->joined && $this->receivedSignal === null) {
            $message = 'snidel will have to wait for the child process is completed. please use Snidel::wait()';
            $this->log->error($message);
            $this->log->info('destruct processes are started.');

            $this->log->info('--> sending a signal to children.');
            $this->sendSignalToChildren(SIGTERM);

            $this->log->info('--> deleting all shared memory.');
            $this->deleteAllData();

            $this->log->info('--> deleting token.');
            unset($this->token);

            $this->log->info('--> destruct processes are finished successfully.');
            throw new LogicException($message);
        }
    }
}
