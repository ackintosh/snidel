<?php
declare(strict_types=1);

namespace Ackintosh\Snidel\Task;

interface TaskInterface
{
    public function getCallable();
    public function getArgs();
    public function getTag();
}
