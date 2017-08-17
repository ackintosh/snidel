<?php
namespace Ackintosh\Snidel\Traits;

use Ackintosh\Snidel\Result\Normalizer as ResultNormalizer;
use Ackintosh\Snidel\Task\Normalizer as TaskNormalizer;
use Bernard\Consumer;
use Bernard\Driver;
use Bernard\Normalizer\EnvelopeNormalizer;
use Bernard\Producer;
use Bernard\QueueFactory;
use Bernard\QueueFactory\PersistentFactory;
use Bernard\Router;
use Bernard\Serializer;
use Normalt\Normalizer\AggregateNormalizer;
use Symfony\Component\EventDispatcher\EventDispatcher;

trait Queueing
{
    /**
     * @param Driver $driver
     * @return PersistentFactory
     */
    private function createFactory(Driver $driver)
    {
        $aggregateNormalizer = new AggregateNormalizer([
            new EnvelopeNormalizer(),
            new TaskNormalizer(),
            new ResultNormalizer()
        ]);

        return new PersistentFactory($driver, new Serializer($aggregateNormalizer));
    }

    /**
     * @param Router $router
     * @return Consumer
     */
    private function createConsumer(Router $router)
    {
        return new Consumer($router, new EventDispatcher());
    }

    /**
     * @param QueueFactory $factory
     * @return Producer
     */
    private function createProducer(QueueFactory $factory)
    {
        return new Producer($factory, new EventDispatcher());
    }
}
