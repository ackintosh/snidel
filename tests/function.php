<?php
function returnsFoo()
{
    return 'foo';
}

function receivesArgumentsAndReturnsIt()
{
    $args = func_get_args();
    return implode('', $args);
}

function sleepsTwoSeconds()
{
    sleep(2);
}
