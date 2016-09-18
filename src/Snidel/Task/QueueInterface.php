<?php
namespace Ackintosh\Snidel\Task;

interface QueueInterface
{
    /**
     * @param   \Ackintosh\Snidel\Task  $task
     * @return  void
     * @throws  RuntimeException
     */
    public function enqueue($task);

    /**
     * @return  \Ackintosh\Snidel\Task
     * @throws  \RuntimeException
     */
    public function dequeue();
}
