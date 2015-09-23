<?php
function returnsFoo()
{
    return 'foo';
}

function receivesArgumentsAndReturnsIt()
{
    return implode('', func_get_args());
}

function sleepsTwoSeconds()
{
    sleep(2);
}
