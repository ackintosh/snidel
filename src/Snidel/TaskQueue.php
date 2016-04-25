<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Task;
use Ackintosh\Snidel\IpcKey;

class TaskQueue
{
    const TASK_MAX_SIZE = 1024;

    private $queuedCount = 0;
    private $dequeuedCount = 0;

    public function __construct($ownerPid)
    {
        $this->ownerPid = $ownerPid;
        $this->ipcKey = new IpcKey($ownerPid, 'snidel_task_' . uniqid((string) mt_rand(1, 100), true));
        $this->id = msg_get_queue($this->ipcKey->generate());
    }

    /**
     * @param   \Ackintosh\Snidel\Task  $task
     * @return  void
     * @throws  RuntimeException
     */
    public function enqueue($task)
    {
        $this->queuedCount++;

        if (!msg_send($this->id, 1, Task::serialize($task))) {
            throw new RuntimeException('failed to enqueue task.');
        }
    }

    /**
     * @return  \Ackintosh\Snidel\Task
     * @throws  \RuntimeException
     */
    public function dequeue()
    {
        $this->dequeuedCount++;
        $msgtype = $serializedTask = null;
        $success = msg_receive($this->id, 1, $msgtype, self::TASK_MAX_SIZE, $serializedTask);

        if (!$success) {
            throw new \RuntimeException('failed to dequeue task');
        }

        return Task::unserialize($serializedTask);
    }

    public function queuedCount()
    {
        return $this->queuedCount;
    }

    public function dequeuedCount()
    {
        return $this->dequeuedCount;
    }

    public function __destruct()
    {
        if ($this->ipcKey->isOwner(getmypid())) {
            $this->ipcKey->delete();
            return msg_remove_queue($this->id);
        }
    }// @codeCoverageIgnore
}
