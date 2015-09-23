<?php
class TestClass
{
    public function returnsFoo()
    {
        return 'foo';
    }

    public function receivesArgumentsAndReturnsIt()
    {
        return implode('', func_get_args());
    }
}
