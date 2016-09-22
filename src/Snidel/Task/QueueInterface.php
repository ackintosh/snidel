<?php
namespace Ackintosh\Snidel\Task;

use Ackintosh\Snidel\Config;

interface QueueInterface
{
    /**
     * @param   \Ackintosh\Snidel\Config
     */
    public function __construct(Config $config);

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

    /**
     * @return  int
     */
    public function queuedCount();

    /**
     * @return  int
     */
    public function dequeuedCount();
}
