<?php
declare(ticks = 1);

namespace Ackintosh;

use Ackintosh\Snidel\Fork\Container;
use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Token;
use Ackintosh\Snidel\Log;
use Ackintosh\Snidel\Pcntl;
use Ackintosh\Snidel\DataRepository;
use Ackintosh\Snidel\MapContainer;
use Ackintosh\Snidel\Task\Task;
use Ackintosh\Snidel\Exception\SharedMemoryControlException;

class Snidel
{
    /** @var string */
    const VERSION = '0.6.3';

    /** @var \Ackintosh\Snidel\Fork\Container */
    private $container;

    /** @var \Ackintosh\Snidel\Pcntl */
    private $pcntl;

    /** @var int */
    private $concurrency;

    /** @var \Ackintosh\Snidel\Log */
    private $log;

    /** @var bool */
    private $joined = false;

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
        $this->concurrency      = $concurrency;
        $this->log              = new Log($this->ownerPid);
        $this->pcntl            = new Pcntl();
        $this->container        = new Container($this->ownerPid, $this->log, $this->concurrency);

        $log    = $this->log;
        $self   = $this;
        foreach ($this->signals as $sig) {
            $this->pcntl->signal(
                $sig,
                function ($sig) use($log, $self) {
                    $log->info('received signal. signo: ' . $sig);
                    $self->setReceivedSignal($sig);

                    $log->info('--> sending a signal to children.');
                    $self->sendSignalToChildren($sig);
                    $log->info('<-- signal handling has been completed successfully.');
                    exit;
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
     * this method uses master / worker model.
     *
     * @param   callable    $callable
     * @param   mixed       $args
     * @param   string      $tag
     * @return  void
     * @throws  \RuntimeException
     */
    public function fork($callable, $args = array(), $tag = null)
    {
        $this->joined = false;

        if (!$this->container->existsMaster()) {
            $this->container->forkMaster();
        }

        try {
            $this->container->enqueue(new Task($callable, $args, $tag));
        } catch (\RuntimeException $e) {
            throw $e;
        }

        $this->log->info('queued task #' . $this->container->queuedCount());
    }

    /**
     * fork process
     * the processes which forked are wait for token.
     *
     * @param   callable                    $callable
     * @param   mixed                       $args
     * @param   string                      $tag
     * @param   \Ackintosh\Snidel\Token     $token
     * @return  void
     * @throws  \RuntimeException
     */
    private function forkChild(Token $token, $callable, $args = array(), $tag = null)
    {
        $task = new Task($callable, $args, $tag);

        try {
            $fork = $this->container->fork();
        } catch (\RuntimeException $e) {
            $this->log->error($e->getMessage());
            throw $e;
        }

        if (getmypid() === $this->ownerPid) {
            // parent
            $this->log->info('created child process. pid: ' . $fork->getPid());
        } else {
            // @codeCoverageIgnoreStart
            // child
            foreach ($this->signals as $sig) {
                $this->pcntl->signal($sig, SIG_DFL, true);
            }

            /**
             * in php5.3, we can not use $this in anonymous functions
             */
            $log = $this->log;
            $resultHasWritten = false;
            register_shutdown_function(function () use (&$resultHasWritten, $fork, $task, $log, $token) {
                if (!$resultHasWritten) {
                    $dataRepository = new DataRepository();
                    $data = $dataRepository->load(getmypid());
                    $result = new Result();
                    $result->setFailure();
                    $result->setTask($task);
                    $result->setFork($fork);
                    try {
                        $data->write($result);
                    } catch (SharedMemoryControlException $e) {
                        throw $e;
                    }
                }

                $log->info('<-- return token.');
                $token->back();
            });

            $log->info('--> waiting for the token come around.');
            if ($token->accept()) {
                $log->info('----> started the function.');
                $result = $task->execute();
                $result->setFork($fork);
                $log->info('<---- completed the function.');
                $dataRepository = new DataRepository();
                $data = $dataRepository->load(getmypid());
                try {
                    $data->write($result);
                } catch (SharedMemoryControlException $e) {
                    throw $e;
                }
                $resultHasWritten = true;
            }

            exit;
            // @codeCoverageIgnoreEnd
        }

        return $fork->getPid();
    }

    /**
     * waits until all tasks that queued by Snidel::fork() are completed
     *
     * @return  void
     */
    public function wait()
    {
        $this->container->wait();
        $this->joined = true;
    }

    /**
     * @return  bool
     */
    public function hasError()
    {
        return $this->container->hasError();
    }

    /**
     * @return  \Ackintosh\Snidel\Error
     */
    public function getError()
    {
        return $this->container->getError();
    }

    /**
     * gets results
     *
     * @param   string  $tag
     * @return  \Ackintosh\Snidel\Result\Collection
     * @throws  \InvalidArgumentException
     */
    public function get($tag = null)
    {
        if (!$this->joined) {
            $this->wait();
        }
        if ($tag !== null && !$this->container->hasTag($tag)) {
            throw new \InvalidArgumentException('unknown tag: ' . $tag);
        }

        return $this->container->getCollection($tag);
    }

    /**
     * sends signal to child
     *
     * @param   int     $sig
     * @return  void
     */
    public function sendSignalToChildren($sig)
    {
        foreach ($this->container->getChildPids() as $pid) {
            $this->log->info('----> sending a signal to child. pid: ' . $pid);
            posix_kill($pid, $sig);
        }
    }

    public function setReceivedSignal($sig)
    {
        $this->receivedSignal = $sig;
    }

    /**
     * delete shared memory
     *
     * @return  void
     * @throws  \Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    private function deleteAllData()
    {
        $dataRepository = new DataRepository();
        try {
            $dataRepository->deleteAll();
        } catch (SharedMemoryControlException $e) {
            throw $e;
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
        $token = new Token($this->ownerPid, $this->concurrency);
        try {
            $this->forkTheFirstProcessing($mapContainer, $token);
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
    private function forkTheFirstProcessing(MapContainer $mapContainer, $token)
    {
        foreach ($mapContainer->getFirstArgs() as $args) {
            try {
                $childPid = $this->forkChild($token, $mapContainer->getFirstMap()->getCallable(), $args);
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
                $result = $this->container->waitForChild();
            } catch (SharedMemoryControlException $e) {
                throw $e;
            }

            $childPid = $result->getFork()->getPid();
            if ($result->isFailure()) {
                $message = 'an error has occurred in child process. pid: ' . $childPid;
                $this->log->error($message);
                throw new \RuntimeException($message);
            }

            if ($nextMap = $mapContainer->nextMap($childPid)) {
                try {
                    $nextMapPid = $this->forkChild(
                        $nextMap->getToken(),
                        $nextMap->getCallable(),
                        $result
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
            $results[] = $this->container->get($pid)->getReturn();
        }

        return $results;
    }

    public function __destruct()
    {
        if ($this->container->existsMaster() && $this->ownerPid === getmypid()) {
            $this->log->info('shutdown master process.');
            $this->container->killMaster();

            unset($this->container);
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
