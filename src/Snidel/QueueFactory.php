<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\Config;

class QueueFactory
{
    /** @var \Ackintosh\Snidel\Config */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    private function createQueue($queueConfig)
    {
        $className = $queueConfig['className'];
        return new $className($this->config);
    }

    public function createTaskQueue()
    {
        return $this->createQueue($this->config->get('taskQueue'));
    }

    public function createResultQueue()
    {
        return $this->createQueue($this->config->get('resultQueue'));
    }
}
