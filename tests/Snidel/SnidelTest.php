<?php
/**
 * @runTestsInSeparateProcesses
 */
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
     * @expectedException RuntimeException
     * @requires PHP 5.3
     */
    public function throwsExceptionWhenFailedToFork()
    {
        $pcntl = $this->getMockBuilder('Snidel_Pcntl')
            ->setMethods(array('fork'))
            ->getMock();
        $pcntl->method('fork')
            ->willReturn(-1);

        $snidel = new Snidel();
        $ref = new ReflectionProperty($snidel, 'pcntl');
        $ref->setAccessible(true);
        $ref->setValue($snidel, $pcntl);

        try {
            $snidel->fork('receivesArgumentsAndReturnsIt', array('bar'));
        } catch (RuntimeException $e) {
            $snidel->wait();
            throw $e;
        }
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
    public function concurrency()
    {
        $concurrency = 3;
        $snidel = new Snidel($concurrency);

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
     * @expectedException InvalidArgumentException
     */
    public function throwsExceptionWhenPassedUnknownTag()
    {
        $snidel = new Snidel();
        $test = new TestClass();

        $snidel->fork(array($test, 'receivesArgumentsAndReturnsIt'), 'bar', 'tag');
        $snidel->get('unknown_tag');
    }

    /**
     * @test
     */
    public function mapRun()
    {
        $snidel = new Snidel();
        $result = $snidel->run($snidel->map(array('FOO', 'BAR'), 'strtolower')->then('ucfirst'));
        $this->isSame($result, array('Foo', 'Bar'));
    }

    /**
     * @test
     */
    public function abnormalExit()
    {
        $snidel = new Snidel();
        $snidel->fork('abnormalExit');
        $snidel->wait();

        $this->assertTrue($snidel->hasError());
    }

    /**
     * @test
     * @requires PHP 5.3
     */
    public function waitSetsErrorWhenChildTerminatesAbnormally()
    {
        // wifexited
        $pcntl = $this->getMockBuilder('Snidel_Pcntl')
            ->setMethods(array('wifexited'))
            ->getMock();
        $pcntl->method('wifexited')
            ->willReturn(false);

        $snidel = new Snidel();
        $snidel->fork('receivesArgumentsAndReturnsIt', array('bar'));
        $ref = new ReflectionProperty($snidel, 'pcntl');
        $ref->setAccessible(true);
        $ref->setValue($snidel, $pcntl);
        $snidel->wait();

        $this->assertTrue($snidel->hasError());

        // wexitstatus
        $pcntl = $this->getMockBuilder('Snidel_Pcntl')
            ->setMethods(array('wexitstatus'))
            ->getMock();
        $pcntl->method('wexitstatus')
            ->willReturn(1);

        $snidel = new Snidel();
        $snidel->fork('receivesArgumentsAndReturnsIt', array('bar'));
        $ref = new ReflectionProperty($snidel, 'pcntl');
        $ref->setAccessible(true);
        $ref->setValue($snidel, $pcntl);
        $snidel->wait();

        $this->assertTrue($snidel->hasError());
    }

    /**
     * @test
     * @expectedException Snidel_Exception_SharedMemoryControlException
     * @requires PHP 5.3
     */
    public function waitThrowsException()
    {
        $data = $this->getMockBuilder('Snidel_Data')
            ->setConstructorArgs(array(getmypid()))
            ->setMethods(array('readAndDelete'))
            ->getMock();
        $data->method('readAndDelete')
            ->will($this->throwException(new Snidel_Exception_SharedMemoryControlException));

        $dataRepository = $this->getMockBuilder('Snidel_DataRepository')
            ->setMethods(array('load'))
            ->getMock();
        $dataRepository->expects($this->any())
            ->method('load')
            ->willReturn($data);

        $snidel = new Snidel();
        $ref = new ReflectionProperty($snidel, 'dataRepository');
        $ref->setAccessible(true);
        $ref->setValue($snidel, $dataRepository);
        $snidel->fork('receivesArgumentsAndReturnsIt', array('bar'));

        try {
            $snidel->wait();
        } catch (Snidel_Exception_SharedMemoryControlException $e) {
            // clean up
            $data->delete();
            throw $e;
        }
    }

    /**
     * @test
     */
    public function waitDoNothingIfAlreadyJoined()
    {
        $snidel = new Snidel();
        $snidel->fork('receivesArgumentsAndReturnsIt', array('bar'));
        $snidel->wait();
        $ret =  $snidel->wait();
        $this->assertNull($ret);
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
