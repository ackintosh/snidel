<?php
class SnidelTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function forkProcessAndReceiveValues()
    {
        $snidel = new Snidel();

        $snidel->fork('receivesArgumentsAndReturnsIt', array('foo'));
        $snidel->fork('receivesArgumentsAndReturnsIt', array('bar'));

        $this->assertTrue($this->isSame($snidel->get(), array('foo', 'bar')));
    }

    /**
     * @test
     */
    public function omitTheSecondArgumentOfFork()
    {
        $snidel = new Snidel();

        $snidel->fork('returnsFoo');

        $this->assertSame($snidel->get(), array('foo'));
    }

    /**
     * @test
     */
    public function passTheValueOtherThanArray()
    {
        $snidel = new Snidel();

        $snidel->fork('receivesArgumentsAndReturnsIt', 'foo');

        $this->assertSame($snidel->get(), array('foo'));
    }

    /**
     * @test
     */
    public function passMultipleArguments()
    {
        $snidel = new Snidel();

        $snidel->fork('receivesArgumentsAndReturnsIt', array('foo', 'bar'));

        $this->assertSame($snidel->get(), array('foobar'));
    }

    /**
     * @test
     */
    public function maxProcs()
    {
        $maxProcs = 3;
        $snidel = new Snidel($maxProcs);

        $start = time();
        $snidel->fork('sleepsTwoSeconds');
        $snidel->fork('sleepsTwoSeconds');
        $snidel->fork('sleepsTwoSeconds');
        $snidel->fork('sleepsTwoSeconds');
        $snidel->get();
        $elapsed = time() - $start;

        $this->assertTrue(4 <= $elapsed && $elapsed < 6);
    }

    /**
     * @test
     */
    public function runInstanceMethod()
    {
        $snidel = new Snidel();
        $test = new TestClass();

        $snidel->fork(array($test, 'returnsFoo'));
        $snidel->fork(array($test, 'receivesArgumentsAndReturnsIt'), 'bar');

        $this->assertTrue($this->isSame($snidel->get(), array('foo', 'bar')));
    }

    /**
     * @test
     * @requires PHP 5.3
     */
    public function runAnonymousFunction()
    {
        $snidel = new Snidel();

        // In order to avoid Parse error in php5.2, `eval` is used.
        eval(<<<__EOS__
\$func = function (\$arg = 'foo') {
    return \$arg;
};
__EOS__
);

        $snidel->fork($func);
        $snidel->fork($func, 'bar');

        $this->assertTrue($this->isSame($snidel->get(), array('foo', 'bar')));
    }

    /**
     * @test
     */
    public function getResultsWithTag()
    {
        $snidel = new Snidel();
        $test = new TestClass();

        $snidel->fork(array($test, 'receivesArgumentsAndReturnsIt'), 'bar1', 'tag1');
        $snidel->fork(array($test, 'receivesArgumentsAndReturnsIt'), 'bar2', 'tag1');
        $snidel->fork(array($test, 'receivesArgumentsAndReturnsIt'), 'bar3', 'tag2');
        $snidel->fork(array($test, 'receivesArgumentsAndReturnsIt'), 'bar4', 'tag2');

        $this->assertTrue($this->isSame($snidel->get('tag1'), array('bar1', 'bar2')));
        $this->assertTrue($this->isSame($snidel->get('tag2'), array('bar3', 'bar4')));
    }

    /**
     * @test
     */
    public function returnsEmptyArrayWhenPassedUnknownTag()
    {
        $snidel = new Snidel();
        $test = new TestClass();

        $snidel->fork(array($test, 'receivesArgumentsAndReturnsIt'), 'bar', 'tag');
        $this->assertSame(array(), $snidel->get('unknown_tag'));
    }

    private function isSame($result, $expect)
    {
        foreach ($result as $r) {
            if ($keys = array_keys($expect, $r, true)) {
                unset($expect[$keys[0]]);
            } else {
                return false;
            }
        }

        return true;
    }
}
