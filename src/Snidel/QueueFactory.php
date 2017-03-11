<?php
namespace Ackintosh\Snidel;

class QueueFactory
{
    /** @var \Ackintosh\Snidel\Config */
    private $config;

    /**
     * QueueFactory constructor.
     * @param \Ackintosh\Snidel\Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * creates queue
     *
     * @param $queueConfig
     * @return mixed
     */
    private function createQueue($queueConfig)
    {
        $className = $queueConfig['className'];
        return new $className($this->config);
    }

    /**
     * creates task queue
     *
     * @return \Ackintosh\Snidel\Task\QueueInterface
     */
    public function createTaskQueue()
    {
        return $this->createQueue($this->config->get('taskQueue'));
    }

    /**
     * creates result queue
     *
     * @return \Ackintosh\Snidel\Result\QueueInterface
     */
    public function createResultQueue()
    {
        return $this->createQueue($this->config->get('resultQueue'));
    }
}
