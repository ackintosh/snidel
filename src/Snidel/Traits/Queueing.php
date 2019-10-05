<?php
declare(strict_types=1);

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
    private function createFactory(Driver $driver): PersistentFactory
    {
        $aggregateNormalizer = new AggregateNormalizer([
            new EnvelopeNormalizer(),
            new TaskNormalizer(),
            new ResultNormalizer()
        ]);

        return new PersistentFactory($driver, new Serializer($aggregateNormalizer));
    }

    private function createConsumer(Router $router): Consumer
    {
        return new Consumer($router, new EventDispatcher());
    }

    private function createProducer(QueueFactory $factory): Producer
    {
        return new Producer($factory, new EventDispatcher());
    }
}
