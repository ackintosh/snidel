<?php
declare(ticks = 1);

namespace Ackintosh;

use Ackintosh\Snidel\ForkContainer;
use Ackintosh\Snidel\Result;
use Ackintosh\Snidel\Token;
use Ackintosh\Snidel\Log;
use Ackintosh\Snidel\Error;
use Ackintosh\Snidel\Pcntl;
use Ackintosh\Snidel\DataRepository;
use Ackintosh\Snidel\MapContainer;
use Ackintosh\Snidel\Exception\SharedMemoryControlException;

class Snidel
{
    /** @var string */
    const VERSION = '0.4.0';

    /** @var array */
    private $childPids = array();

    /** @var \Ackintosh\Snidel\ForkContainer */
    private $forkContainer;

    /** @var \Ackintosh\Snidel\Error */
    private $error;

    /** @var \Ackintosh\Snidel\Pcntl */
    private $pcntl;

    /** @var int */
    private $concurrency;

    /** @var \Ackintosh\Snidel\Token */
    private $token;

    /** @var \Ackintosh\Snidel\Log */
    private $log;

    /** @var \Ackintosh\Snidel\DataRepository */
    private $dataRepository;

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

    /** @var \Ackintosh\Snidel\Token */
    private $processToken;

    /** @var array */
    private $processInformation = array();

    /** @var bool */
    private $exceptionHasOccured = false;

    public function __construct($concurrency = 5)
    {
        $this->ownerPid         = getmypid();
        $this->childPids        = array();
        $this->concurrency      = $concurrency;
        $this->token            = new Token(getmypid(), $concurrency);
        $this->log              = new Log(getmypid());
        $this->error            = new Error();
        $this->pcntl            = new Pcntl();
        $this->dataRepository   = new DataRepository();
        $this->forkContainer    = new ForkContainer();

        foreach ($this->signals as $sig) {
            $this->pcntl->signal($sig, array($this, 'signalHandler'), false);
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
     * @throws  \RuntimeException
     */
    public function fork($callable, $args = array(), $tag = null, Token $token = null)
    {
        $this->processToken = $token ? $token : $this->token;
        if (!is_array($args)) {
            $args = array($args);
        }

        try {
            $fork = $this->forkContainer->fork();
        } catch (\RuntimeException $e) {
            $this->log->error($e->getMessage());
            throw $e;
        }

        $fork->setCallable($callable);
        $fork->setArgs($args);

        if ($pid = $fork->getPid()) {
            // parent
            $this->log->info('created child process. pid: ' . $pid);
            $this->childPids[] = $pid;
            if ($tag !== null) {
                $this->tagsToPids[$tag][] = $pid;
            }
        } else {
            // @codeCoverageIgnoreStart
            // child
            foreach ($this->signals as $sig) {
                $this->pcntl->signal($sig, SIG_DFL, true);
            }

            $result = new Result();
            register_shutdown_function(function () use ($result) {
                $data = $this->dataRepository->load(getmypid());
                try {
                    $data->write($result);
                } catch (SharedMemoryControlException $e) {
                    throw $e;
                }
                $this->log->info('<-- return token.');
                $this->processToken->back();
            });

            $this->log->info('--> waiting for the token come around.');
            if ($this->processToken->accept()) {
                $this->log->info('----> started the function.');
                $result->setReturn(call_user_func_array($callable, $args));
                $this->log->info('<---- completed the function.');
            }

            $this->_exit();
            // @codeCoverageIgnoreEnd
        }

        return $pid;
    }

    /**
     * waits until all children are completed
     *
     * @return  void
     * @throws  \Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function wait()
    {
        if ($this->joined) {
            return;
        }

        $count = count($this->childPids);
        for ($i = 0; $i < $count; $i++) {
            try {
                $fork = $this->forkContainer->wait();
            } catch (SharedMemoryControlException $e) {
                $this->exceptionHasOccured = true;
                throw $e;
            }

            $childPid   = $fork->getPid();
            $result     = $fork->getResult();
            if (!$fork->isSuccessful()) {
                $message = 'an error has occurred in child process. pid: ' . $childPid;
                $this->log->error($message);
                $this->error[$childPid] = array(
                    'status'    => $fork->getStatus(),
                    'message'   => $message,
                    'callable'  => $fork->getCallable(),
                    'args'      => $fork->getArgs(),
                    'return'    => $result->getReturn(),
                );
            } else {
                $this->results[$childPid] = $result->getReturn();
            }
            unset($this->childPids[array_search($childPid, $this->childPids)]);
        }
        $this->joined = true;
    }

    /**
     * @return  bool
     */
    public function hasError()
    {
        return $this->error->exists();
    }

    /**
     * @return  \Ackintosh\Snidel\Error
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * gets results
     *
     * @param   string  $tag
     * @return  array   $ret
     * @throws  \InvalidArgumentException
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
            } catch (\InvalidArgumentException $e) {
                throw $e;
            }
        }
    }

    /**
     * gets results with tag
     *
     * @param   string  $tag
     * @return  array   $results
     * @throws  \InvalidArgumentException
     */
    private function getWithTag($tag)
    {
        if (!isset($this->tagsToPids[$tag])) {
            throw new \InvalidArgumentException('unknown tag: ' . $tag);
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
    public function signalHandler($sig)
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
     * @throws  \Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    private function deleteAllData()
    {
        foreach ($this->childPids as $pid) {
            $data = $this->dataRepository->load($pid);
            try {
                $data->deleteIfExists();
            } catch (SharedMemoryControlException $e) {
                throw $e;
            }
        }
    }

    /**
     * create map object
     *
     * @param   array       $args
     * @param   callable    $callable
     * @return  \Ackintosh\Snidel\MapContainer
     */
    public function map(Array $args, $callable)
    {
        return new MapContainer($args, $callable, $this->concurrency);
    }

    /**
     * run map object
     *
     * @param   \Ackintosh\Snidel\MapContainer
     * @return  array
     * @throws  \RuntimeException
     */
    public function run(MapContainer $mapContainer)
    {
        try {
            $this->forkTheFirstProcessing($mapContainer);
            $this->waitsAndConnectsProcess($mapContainer);
        } catch (\RuntimeException $e) {
            $this->exceptionHasOccured = true;
            throw $e;
        }

        return $this->getResultsOf($mapContainer);
    }

    /**
     * fork the first processing of the map container
     *
     * @param   \Ackintosh\Snidel\MapContainer
     * @return  void
     * @throws  \RuntimeException
     */
    private function forkTheFirstProcessing(MapContainer $mapContainer)
    {
        foreach ($mapContainer->getFirstArgs() as $args) {
            try {
                $childPid = $this->fork($mapContainer->getFirstMap()->getCallable(), $args);
            } catch (\RuntimeException $e) {
                throw $e;
            }
            $mapContainer->getFirstMap()->countTheForked();
            $mapContainer->getFirstMap()->addChildPid($childPid);
        }
    }

    /**
     * waits and connects the process of map container
     *
     * @param   \Ackintosh\Snidel\MapContainer
     * @return  void
     * @throws  \RuntimeException
     */
    private function waitsAndConnectsProcess(MapContainer $mapContainer)
    {
        if ($this->joined) {
            return;
        }

        while ($mapContainer->isProcessing()) {
            try {
                $fork = $this->forkContainer->wait();
            } catch (SharedMemoryControlException $e) {
                throw $e;
            }

            $childPid = $fork->getPid();
            if (!$fork->isSuccessful()) {
                $message = 'an error has occurred in child process. pid: ' . $childPid;
                $this->log->error($message);
                throw new \RuntimeException($message);
            }

            $result = $fork->getResult();
            $this->results[$childPid] = $result->getReturn();
            unset($this->childPids[array_search($childPid, $this->childPids)]);
            if ($nextMap = $mapContainer->nextMap($childPid)) {
                try {
                    $nextMapPid = $this->fork(
                        $nextMap->getCallable(),
                        $fork,
                        null,
                        $nextMap->getToken()
                    );
                } catch (\RuntimeException $e) {
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
     * @param   \Ackintosh\Snidel\MapContainer
     * @return  array
     */
    private function getResultsOf(MapContainer $mapContainer)
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
        if ($this->exceptionHasOccured) {
            $this->log->info('destruct processes are started.(exception has occured)');
            $this->log->info('--> deleting all shared memory.');
            $this->deleteAllData();
        } elseif ($this->ownerPid === getmypid() && !$this->joined && $this->receivedSignal === null) {
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
            throw new \LogicException($message);
        }
    }
}
