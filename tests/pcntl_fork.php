<?php
namespace Ackintosh\Snidel;

/**
 * @codeCoverageIgnore
 */
function pcntl_fork()
{
    return -1;
}
