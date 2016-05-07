<?php
namespace Ackintosh\Snidel;

interface TaskInterface
{
    public function getCallable();
    public function getArgs();
    public function getTag();
}
