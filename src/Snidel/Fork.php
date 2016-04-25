<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\DataRepository;
use Ackintosh\Snidel\Pcntl;
use Ackintosh\Snidel\Result;
use Ackintosh\Snidel\Task;
use Ackintosh\Snidel\Exception\SharedMemoryControlException;

class Fork
{
    /** @var int */
    private $pid;

    /** @var \Ackintosh\Snidel\Pcntl */
    private $pcntl;

    /** @var \Ackintosh\Snidel\DataRepository */
    private $dataRepository;

    /** @var int */
    private $status;

    /** @var callable */
    private $callable;

    /** @var array */
    private $args;

    /** @var \Ackintosh\Snidel\Result */
    private $result;

    /** @var string */
    private $tag;

    /** @var \Ackintosh\Snidel\Task */
    private $task;

    /** @var string */
    private $serializedTask;

    /** @var bool */
    private $queued = false;

    /**
     * @param   int     $pid
     */
    public function __construct($pid)
    {
        $this->pid              = $pid;
        $this->pcntl            = new Pcntl();
        $this->dataRepository   = new DataRepository();
    }

    /**
     * set exit status
     *
     * @param   int     $status
     * @return  void
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * return pid
     *
     * @return  int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * return exit status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param   \Ackintosh\Snidel\Task
     * @return  void
     */
    public function setTask($task)
    {
        $this->task = $task;
    }

    /**
     * set callable
     *
     * @param   callable    $callable
     * @return  void
     */
    public function setCallable($callable)
    {
        $this->callable = $callable instanceof \Closure ? '*Closure*' : $callable;
    }

    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * set arguments
     *
     * @param   array   $args
     * @return  void
     */
    public function setArgs($args)
    {
        $this->args = $args;
    }

    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @return bool
     */
    public function hasFinishedSuccessfully()
    {
        return $this->pcntl->wifexited($this->status) && $this->pcntl->wexitstatus($this->status) === 0;
    }

    /**
     * @return bool
     */
    public function hasNotFinishedSuccessfully()
    {
        return !$this->hasFinishedSuccessfully();
    }


    /**
     * load result
     *
     * @return void
     * @throws \Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function loadResult()
    {
        try {
            $this->setResult($this->dataRepository->load($this->pid)->readAndDelete());
        } catch (SharedMemoryControlException $e) {
            throw $e;
        }
    }

    /**
     *
     * @param   \Ackintosh\Snidel\Result
     * @return  void
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * return result
     *
     * @return \Ackintosh\Snidel\Result
     */
    public function getResult()
    {
        return $this->result;
    }

    public function hasNoResult()
    {
        return $this->result === null;
    }

    /**
     * @param   string  $tag
     * @return  void
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
    }

    /**
     * @return  string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @return  void
     */
    public function setQueued()
    {
        $this->queued = true;
    }

    /**
     * @return  bool
     */
    public function isQueued()
    {
        return $this->queued;
    }

    public function executeTask()
    {
        ob_start();
        $result = new Result();
        $result->setReturn(call_user_func_array($this->task->getCallable(), $this->task->getArgs()));
        $result->setOutput(ob_get_clean());
        $this->result = $result;
    }

    /**
     * @param   \Ackintosh\Snidel\Fork  $fork
     * @return  string
     */
    public static function serialize($fork)
    {
        $fork->serializeTask();

        return serialize($fork);
    }

    /**
     * @return  void
     */
    private function serializeTask()
    {
        $this->serializedTask = Task::serialize($this->task);
        unset($this->task);
    }

    /**
     * @param   string  $serializedFork
     * @return  \Ackintosh\Snidel\Fork
     */
    public static function unserialize($serializedFork)
    {
        $fork = unserialize($serializedFork);
        $fork->unserializeTask();

        return $fork;
    }

    /**
     * @return  void
     */
    private function unserializeTask()
    {
        $this->task = Task::unserialize($this->serializedTask);
        unset($this->serializedTask);
    }
}
