<?php
class TestClass
{
    public function returnsFoo()
    {
        return 'foo';
    }

    public function receivesArgumentsAndReturnsIt()
    {
        $args = func_get_args();
        return implode('', $args);
    }
}
