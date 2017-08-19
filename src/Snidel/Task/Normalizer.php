<?php
namespace Ackintosh\Snidel\Task;

use Opis\Closure\SerializableClosure;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class Normalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Task;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === 'Ackintosh\Snidel\Task\Task';
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        $callable = $object->getCallable();

        return serialize(new Task(
            (self::isClosure($callable) ? new SerializableClosure($callable) : $callable),
            $object->getArgs(),
            $object->getTag()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        $task = unserialize($data);
        if (self::isClosure($callable = $task->getCallable())) {
            $task = new Task(
                $callable->getClosure(),
                $task->getArgs(),
                $task->getTag()
            );
        }

        return $task;
    }

    /**
     * @param   mixed   $callable
     * @return  bool
     */
    private static function isClosure($callable)
    {
        return is_object($callable) && is_callable($callable);
    }
}
