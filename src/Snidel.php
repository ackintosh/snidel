<?php
declare(ticks=1);

namespace Ackintosh;

use Ackintosh\Snidel\Config;
use Ackintosh\Snidel\Error;
use Ackintosh\Snidel\Fork\Coordinator;
use Ackintosh\Snidel\Log;
use Ackintosh\Snidel\Pcntl;
use Ackintosh\Snidel\Task\Task;

class Snidel
{
    /** @var \Ackintosh\Snidel\Config */
    private $config;

    /** @var \Ackintosh\Snidel\Fork\Coordinator */
    private $coordinator;

    /** @var \Ackintosh\Snidel\Log */
    private $log;

    /** @var array */
    private $signals = [
        SIGTERM,
        SIGINT,
    ];

    /**
     * @param   array $parameter
     * @throws \RuntimeException
     */
    public function __construct(array $parameter = [])
    {
        $this->config = new Config($parameter);
        $this->log = new Log($this->config->get('ownerPid'), $this->config->get('logger'));
        $this->coordinator = new Coordinator($this->config, $this->log);
        $this->coordinator->forkMaster();
        $this->registerSignalHandler($this->coordinator, $this->log);
        $this->log->info('parent pid: ' . $this->config->get('ownerPid'));
    }

    /**
     * this method uses master / worker model.
     *
     * @param   callable    $callable
     * @param   mixed       $args
     * @param   string      $tag
     * @throws  \RuntimeException
     */
    public function process(callable $callable, $args = [], ?string $tag = null): void
    {
        try {
            $this->coordinator->enqueue(new Task($callable, $args, $tag));
        } catch (\RuntimeException $e) {
            $this->log->error('failed to enqueue the task: ' . $e->getMessage());
            throw $e;
        }

        $this->log->info('queued task #' . $this->coordinator->queuedCount());
    }

    /**
     * waits until all tasks that queued by Snidel::fork() are completed
     */
    public function wait(): void
    {
        $this->coordinator->wait();
    }

    /**
     * returns generator which returns a result
     */
    public function results(): \Generator
    {
        foreach($this->coordinator->results() as $r) {
            yield $r;
        }
    }

    public function hasError(): bool
    {
        return $this->coordinator->hasError();
    }

    public function getError(): Error
    {
        return $this->coordinator->getError();
    }

    /**
     * @param Coordinator $coordinator
     * @param Log $log
     */
    private function registerSignalHandler(Coordinator$coordinator, Log $log): void
    {
        $pcntl = new Pcntl();
        foreach ($this->signals as $sig) {
            $pcntl->signal(
                $sig,
                function ($sig) use ($log, $coordinator) {
                    $log->info('received signal. signo: ' . $sig);
                    $log->info('--> sending a signal " to children.');
                    $coordinator->sendSignalToMaster($sig);
                    $log->info('<-- signal handling has been completed successfully.');
                    exit;
                },
                false
            );
        }
    }

    public function __destruct()
    {
        if ($this->config->get('ownerPid') === getmypid()) {
            $this->wait();
            if ($this->coordinator->existsMaster()) {
                $this->log->info('shutdown master process.');
                $this->coordinator->sendSignalToMaster();
            }

            unset($this->coordinator);
        }
    }
}
