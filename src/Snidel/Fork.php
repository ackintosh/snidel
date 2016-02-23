<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\DataRepository;
use Ackintosh\Snidel\Pcntl;
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
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->pcntl->wifexited($this->status) && $this->pcntl->wexitstatus($this->status) === 0;
    }

    /**
     * return result
     *
     * @return array
     * @throws \Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function getResult()
    {
        try {
            $data = $this->dataRepository->load($this->pid);
            return $data->readAndDelete();
        } catch (SharedMemoryControlException $e) {
            throw $e;
        }
    }
}
