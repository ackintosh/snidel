<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\AbstractQueue;
use Ackintosh\Snidel\TaskFormatter;

class TaskQueue extends AbstractQueue
{
    /**
     * @param   \Ackintosh\Snidel\Task  $task
     * @return  void
     * @throws  RuntimeException
     */
    public function enqueue($task)
    {
        $this->queuedCount++;

        $serialized = TaskFormatter::serialize($task);
        if ($this->isExceedsLimit($serialized)) {
            throw new \RuntimeException('the task exceeds the message queue limit.');
        }

        if (!$this->sendMessage($serialized)) {
            throw new \RuntimeException('failed to enqueue task.');
        }
    }

    /**
     * @return  \Ackintosh\Snidel\Task
     * @throws  \RuntimeException
     */
    public function dequeue()
    {
        $this->dequeuedCount++;
        try {
            $serializedTask = $this->receiveMessage();
        } catch (\RuntimeException $e) {
            throw new \RuntimeException('failed to dequeue task');
        }

        return TaskFormatter::unserialize($serializedTask);
    }
}
