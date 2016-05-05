<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\AbstractQueue;
use Ackintosh\Snidel\Task;

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

        if (!$this->sendMessage(Task::serialize($task))) {
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

        return Task::unserialize($serializedTask);
    }
}
