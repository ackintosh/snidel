<?php
declare(ticks = 1);

namespace Ackintosh;

use Ackintosh\Snidel\ForkContainer;
use Ackintosh\Snidel\ForkCollection;
use Ackintosh\Snidel\Result;
use Ackintosh\Snidel\Token;
use Ackintosh\Snidel\Log;
use Ackintosh\Snidel\Error;
use Ackintosh\Snidel\Pcntl;
use Ackintosh\Snidel\DataRepository;
use Ackintosh\Snidel\MapContainer;
use Ackintosh\Snidel\TaskQueue;
use Ackintosh\Snidel\ResultQueue;
use Ackintosh\Snidel\Exception\SharedMemoryControlException;

class Snidel
{
    /** @var string */
    const VERSION = '0.5.0';

    private $masterProcessId = null;

    /** @var array */
    private $childPids = array();

    /** @var \Ackintosh\Snidel\ForkContainer */
    private $forkContainer;

    /**  @var array */
    private $forks = array();

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
    private $signals = array(
        SIGTERM,
        SIGINT,
    );

    /** @var int */
    private $receivedSignal;

    /** @var \Ackintosh\Snidel\Token */
    private $processToken;

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
        $this->taskQueue        = new TaskQueue(getmypid());
        $this->resultQueue      = new ResultQueue(getmypid());

        foreach ($this->signals as $sig) {
            $this->pcntl->signal(
                $sig,
                function ($sig) {
                    $this->log->info('received signal. signo: ' . $sig);
                    $this->receivedSignal = $sig;

                    $this->log->info('--> sending a signal to children.');
                    $this->sendSignalToChildren($sig);

                    $this->log->info('--> deleting token.');
                    unset($this->token);

                    $this->log->info('<-- signal handling has been completed successfully.');
                    $this->_exit();
                },
                false
            );
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
     * @param   mixed       $args
     * @param   string      $tag
     * @return  void
     * @throws  \RuntimeException
     */
    public function fork($callable, $args = array(), $tag = null)
    {
        if (!is_array($args)) {
            $args = array($args);
        }

        if ($this->masterProcessId === null) {
            $this->forkMaster();
        }

        try {
            $this->taskQueue->enqueue($callable, $args, $tag);
        } catch (\RuntimeException $e) {
            throw $e;
        }

        $this->log->info('queued task #' . $this->taskQueue->queuedCount());
    }

    /**
     * fork master process
     *
     * @return  void
     */
    private function forkMaster()
    {
        $pid = $this->pcntl->fork();
        $this->masterProcessId = ($pid === 0) ? getmypid() : $pid;
        $this->log->setMasterProcessId($this->masterProcessId);

        if ($pid) {
            // owner
            $this->log->info('pid: ' . getmypid());
        } elseif ($pid === -1) {
            // error
        } else {
            // master
            $this->log->info('pid: ' . $this->masterProcessId);
            foreach ($this->signals as $sig) {
                $this->pcntl->signal($sig, SIG_DFL, true);
            }
            while ($task = $this->taskQueue->dequeue()) {
                $this->log->info('dequeued task #' . $this->taskQueue->dequeuedCount());
                if ($this->token->accept()) {
                    $this->forkWorker($task['callable'], $task['args'], $task['tag']);
                }
            }
            $this->_exit();
        }
    }

    /**
     * fork worker process
     *
     * @param   callable    $callable
     * @param   mixed       $args
     * @param   string      $tag
     * @return  void
     * @throws  \RuntimeException
     */
    private function forkWorker($callable, $args = array(), $tag = null)
    {
        if (!is_array($args)) {
            $args = array($args);
        }

        try {
            $fork = $this->forkContainer->fork($tag);
        } catch (\RuntimeException $e) {
            $this->log->error($e->getMessage());
            throw $e;
        }

        $fork->setCallable($callable);
        $fork->setArgs($args);
        $fork->setTag($tag);

        if (getmypid() === $this->masterProcessId) {
            // master
            $this->log->info('forked worker. pid: ' . $fork->getPid());
        } else {
            // worker
            $this->log->info('has forked. pid: ' . getmypid());
            // @codeCoverageIgnoreStart
            foreach ($this->signals as $sig) {
                $this->pcntl->signal($sig, SIG_DFL, true);
            }

            $resultHasQueued = false;
            register_shutdown_function(function () use ($fork, &$resultHasQueued) {
                if ($fork->hasNoResult() || $resultHasQueued === false) {
                    $result = new Result();
                    $result->setFailure();
                    $fork->setResult($result);
                    $this->resultQueue->enqueue($fork);
                }
            });
            $this->log->info('----> started the function.');
            ob_start();
            $result = new Result();
            $result->setReturn(call_user_func_array($callable, $args));
            $result->setOutput(ob_get_clean());
            $fork->setResult($result);
            $this->log->info('<---- completed the function.');

            $this->resultQueue->enqueue($fork);
            $resultHasQueued = true;
            $this->log->info('queued the result.');
            $this->token->back();
            $this->log->info('return the token and exit.');
            $this->_exit();
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * fork process
     * this method does't use a master / worker model.
     *
     * @param   callable                    $callable
     * @param   mixed                       $args
     * @param   string                      $tag
     * @param   \Ackintosh\Snidel\Token     $token
     * @return  void
     * @throws  \RuntimeException
     */
    public function forkSimply($callable, $args = array(), $tag = null, Token $token = null)
    {
        $this->processToken = $token ? $token : $this->token;
        if (!is_array($args)) {
            $args = array($args);
        }

        try {
            $fork = $this->forkContainer->fork($tag);
        } catch (\RuntimeException $e) {
            $this->log->error($e->getMessage());
            throw $e;
        }

        $fork->setCallable($callable);
        $fork->setArgs($args);

        if (getmypid() === $this->ownerPid) {
            // parent
            $this->log->info('created child process. pid: ' . $fork->getPid());
            $this->childPids[] = $fork->getPid();
        } else {
            // @codeCoverageIgnoreStart
            // child
            foreach ($this->signals as $sig) {
                $this->pcntl->signal($sig, SIG_DFL, true);
            }

            $result = new Result();
            /**
             * in php5.3, we can not use $this in anonymous functions
             */
            $dataRepository     = $this->dataRepository;
            $log                = $this->log;
            $processToken       = $this->processToken;
            register_shutdown_function(function () use ($result, $dataRepository, $log, $processToken) {
                $data = $dataRepository->load(getmypid());
                try {
                    $data->write($result);
                } catch (SharedMemoryControlException $e) {
                    throw $e;
                }
                $log->info('<-- return token.');
                $processToken->back();
            });

            $log->info('--> waiting for the token come around.');
            if ($processToken->accept()) {
                $log->info('----> started the function.');
                ob_start();
                $result->setReturn(call_user_func_array($callable, $args));
                $result->setOutput(ob_get_clean());
                $log->info('<---- completed the function.');
            }

            $this->_exit();
            // @codeCoverageIgnoreEnd
        }

        return $fork->getPid();
    }

    /**
     * waits until all children that has forked by Snidel::forkSimply() are completed
     *
     * @return  void
     * @throws  \Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function waitSimply()
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
            if ($fork->hasNotFinishedSuccessfully()) {
                $message = 'an error has occurred in child process. pid: ' . $childPid;
                $this->log->error($message);
                $this->error[$childPid] = array(
                    'status'    => $fork->getStatus(),
                    'message'   => $message,
                    'callable'  => $fork->getCallable(),
                    'args'      => $fork->getArgs(),
                    'return'    => $result->getReturn(),
                );
            }
            unset($this->childPids[array_search($childPid, $this->childPids)]);
        }
        $this->joined = true;
    }

    /**
     * waits until all tasks that queued by Snidel::fork() are completed
     *
     * @return  void
     */
    public function wait()
    {
        for (; $this->taskQueue->queuedCount() > $this->resultQueue->dequeuedCount();) {
            $fork = $this->resultQueue->dequeue();
            if ($fork->getResult()->isFailure()) {
                $this->error[$fork->getPid()] = $fork;
            }
            $this->forks[] = $fork;
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
     * @return  \Ackintosh\Snidel\ForkCollection
     * @throws  \InvalidArgumentException
     */
    public function getSimply($tag = null)
    {
        if (!$this->joined) {
            $this->waitSimply();
        }

        if ($tag === null) {
            return $this->forkContainer->getCollection();
        }

        if (!$this->forkContainer->hasTag($tag)) {
            throw new \InvalidArgumentException('unknown tag: ' . $tag);
        }

        return $this->forkContainer->getCollection($tag);
    }

    /**
     * gets results
     *
     * @param   string  $tag
     * @return  \Ackintosh\Snidel\ForkCollection
     * @throws  \InvalidArgumentException
     */
    public function get($tag = null)
    {
        if (!$this->joined) {
            $this->wait();
        }

        if ($this->taskQueue->queuedCount() === 0) {
            return;
        }

        if ($tag === null) {
            return new ForkCollection($this->forks);
        }

        $filtered = array_filter($this->forks, function ($fork) use ($tag) {
            return $fork->getTag() === $tag;
        });

        if (count($filtered) === 0) {
            throw new \InvalidArgumentException('unknown tag: ' . $tag);
        }

        return new ForkCollection($filtered);
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
                $childPid = $this->forkSimply($mapContainer->getFirstMap()->getCallable(), $args);
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
            if ($fork->hasNotFinishedSuccessfully()) {
                $message = 'an error has occurred in child process. pid: ' . $childPid;
                $this->log->error($message);
                throw new \RuntimeException($message);
            }

            unset($this->childPids[array_search($childPid, $this->childPids)]);
            if ($nextMap = $mapContainer->nextMap($childPid)) {
                try {
                    $nextMapPid = $this->forkSimply(
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
            $results[] = $this->forkContainer->get($pid)->getResult()->getReturn();
        }

        return $results;
    }

    private function _exit($status = 0)
    {
        exit($status);
    }

    public function __destruct()
    {
        if ($this->masterProcessId !== null && $this->ownerPid === getmypid()) {
            $this->log->info('shutdown master process.');
            posix_kill($this->masterProcessId, SIGTERM);

            unset($this->taskQueue);
            unset($this->resultQueue);
        }

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
