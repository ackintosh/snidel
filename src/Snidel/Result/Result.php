<?php
declare(strict_types=1);

namespace Ackintosh\Snidel\Result;

use Ackintosh\Snidel\Fork\Process;
use Ackintosh\Snidel\Task\Task;
use Bernard\Message\AbstractMessage;

class Result extends AbstractMessage
{
    /** @var mixed */
    private $return;

    /** @var string */
    private $output;

    /** @var \Ackintosh\Snidel\Fork\Process|null */
    private $process;

    /** @var \Ackintosh\Snidel\Task\Task|null */
    private $task;

    /** @var bool */
    private $failure = false;

    /** @var array */
    private $error;

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Result';
    }

    /**
     * set return
     *
     * @param   mixed     $return
     */
    public function setReturn($return): void
    {
        $this->return = $return;
    }

    /**
     * return return value
     *
     * @return  mixed
     */
    public function getReturn()
    {
        return $this->return;
    }

    /**
     * set output
     */
    public function setOutput(string $output): void
    {
        $this->output = $output;
    }

    /**
     * return output
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    public function setProcess(?Process $process): void
    {
        $this->process = $process;
    }

    public function getProcess(): ?Process
    {
        return $this->process;
    }

    public function setTask(?Task $task): void
    {
        $this->task = $task;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function setError(?array $error): void
    {
        $this->failure  = true;
        $this->error    = $error;
    }

    public function getError(): ?array
    {
        return $this->error;
    }

    public function isFailure(): bool
    {
        return $this->failure;
    }

    public function __clone()
    {
        // to avoid point to same object.
        $this->task = clone $this->task;
    }
}
