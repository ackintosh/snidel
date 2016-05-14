<?php
namespace Ackintosh\Snidel\Task;

use Ackintosh\Snidel\Task\TaskInterface;

class MinifiedTask implements TaskInterface
{
    /** @var string */
    private $tag;

    /**
     * @param   string  $tag
     */
    public function __construct($tag)
    {
        $this->tag = $tag;
    }

    /**
     * @return  null
     */
    public function getCallable()
    {
        return;
    }

    /**
     * @return  null
     */
    public function getArgs()
    {
        return;
    }

    /**
     * @return  string
     */
    public function getTag()
    {
        return $this->tag;
    }
}
