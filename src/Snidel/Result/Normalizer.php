<?php
declare(strict_types=1);

namespace Ackintosh\Snidel\Result;

use Bernard\Normalizer\AbstractAggregateNormalizerAware;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @codeCoverageIgnore
 * It has been covered by SnidelTest
 */
class Normalizer extends AbstractAggregateNormalizerAware implements NormalizerInterface, DenormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === 'Ackintosh\Snidel\Result\Result';
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $cloned = clone $object;
        $serializedTask = $this->aggregate->normalize($cloned->getTask());
        $serializedProcess = serialize($cloned->getProcess());
        $cloned->setTask(null);
        $cloned->setProcess(null);

        return serialize([
            'serializedTask' => $serializedTask,
            'serializedProcess' => $serializedProcess,
            'result' => $cloned,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $unserialized = unserialize($data);
        $unserialized['result']->setTask($this->aggregate->denormalize($unserialized['serializedTask'], 'Ackintosh\Snidel\Task\Task'));
        $unserialized['result']->setProcess(unserialize($unserialized['serializedProcess']));

        return $unserialized['result'];
    }
}
