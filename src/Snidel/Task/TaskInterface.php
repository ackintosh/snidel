<?php
namespace Ackintosh\Snidel\Task;

interface TaskInterface
{
    public function getCallable();
    public function getArgs();
    public function getTag();
}
