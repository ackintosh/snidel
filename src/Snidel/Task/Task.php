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

    /**
     * @param   callable    $callable
     * @param   mixed       $args
     * @param   string      $tag
     */
    public function __construct(callable $callable, $args, ?string $tag)
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

    /**
     * @return  callable
     */
    public function getCallable(): callable
    {
        return $this->callable;
    }

    /**
     * @return  mixed
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @return  string|null
     */
    public function getTag(): ?string
    {
        return $this->tag;
    }

    /**
     * @return  \Ackintosh\Snidel\Result\Result
     */
    public function execute(): Result
    {
        ob_start();
        $result = new Result();

        try {
            $result->setReturn(
                call_user_func_array(
                    $this->getCallable(),
                    (is_array($args = $this->getArgs())) ? $args : [$args]
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
