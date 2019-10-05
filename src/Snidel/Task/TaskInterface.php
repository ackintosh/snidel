<?php
declare(strict_types=1);

namespace Ackintosh\Snidel\Task;

interface TaskInterface
{
    public function getCallable(): callable;
    public function getArgs(): array;
    public function getTag(): ?string;
}
