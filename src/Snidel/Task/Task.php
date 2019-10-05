<?php
namespace Ackintosh\Snidel\Task;

use Ackintosh\Snidel\Result\Result;
use Bernard\Message\AbstractMessage;

class Task extends AbstractMessage implements TaskInterface
{
    /** @var callable */
    private $callable;

    /** @var array */
    private $args;

    /** @var string */
    private $tag;

    public function __construct(callable $callable, array $args, ?string $tag)
    {
        $this->callable     = $callable;
        $this->args         = $args;
        $this->tag          = $tag;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Task';
    }

    public function getCallable(): callable
    {
        return $this->callable;
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function execute(): Result
    {
        ob_start();
        $result = new Result();

        try {
            $result->setReturn(
                call_user_func_array(
                    $this->getCallable(),
                    $this->getArgs()
                )
            );
        } catch (\RuntimeException $e) {
            ob_get_clean();
            throw $e;
        }

        $result->setOutput(ob_get_clean());
        $result->setTask($this);

        return $result;
    }
}
