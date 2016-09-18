<?php
use Ackintosh\Snidel\TestCase;
use Ackintosh\Snidel\Fork\Container;
use Ackintosh\Snidel\Log;
use Ackintosh\Snidel\Pcntl;
use Ackintosh\Snidel\Task\Task;

/**
 * @runTestsInSeparateProcesses
 */
class ContainerTest extends TestCase
{
    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function enqueueThrowsExceptionWhenFailed()
    {
        $container = $this->makeForkContainer();
        $task = new Task(
            function ($args) {
                return $args;
            }, 
            'foo',
            null
        );

        require_once(__DIR__ . '/../../msg_send.php');
        $container->enqueue($task);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function forkThrowsExceptionWhenFailed()
    {
        $pcntl = $this->getMockBuilder('\Ackintosh\Snidel\Pcntl')
            ->setMethods(array('fork'))
            ->getMock();

        $pcntl->expects($this->once())
            ->method('fork')
            ->will($this->returnValue(-1));

        $container = $this->makeForkContainer();
        $prop = new \ReflectionProperty($container, 'pcntl');
        $prop->setAccessible(true);
        $prop->setValue($container, $pcntl);

        $method = new \ReflectionMethod('\Ackintosh\Snidel\Fork\Container', 'fork');
        $method->setAccessible(true);

        $method->invoke($container);
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function forkWorkerThrowsExceptionWhenFailed()
    {
        $pcntl = $this->getMockBuilder('\Ackintosh\Snidel\Pcntl')
            ->setMethods(array('fork'))
            ->getMock();

        $pcntl->expects($this->once())
            ->method('fork')
            ->will($this->returnValue(-1));

        $container = $this->makeForkContainer();
        $prop = new \ReflectionProperty($container, 'pcntl');
        $prop->setAccessible(true);
        $prop->setValue($container, $pcntl);

        $method = new \ReflectionMethod('\Ackintosh\Snidel\Fork\Container', 'forkWorker');
        $method->setAccessible(true);

        $method->invoke($container, $this->makeTask());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function forkMasterThrowsExceptionWhenFailed()
    {
        $pcntl = $this->getMockBuilder('\Ackintosh\Snidel\Pcntl')
            ->setMethods(array('fork'))
            ->getMock();

        $pcntl->expects($this->once())
            ->method('fork')
            ->will($this->returnValue(-1));

        $container = $this->makeForkContainer();
        $prop = new \ReflectionProperty($container, 'pcntl');
        $prop->setAccessible(true);
        $prop->setValue($container, $pcntl);

        $container->forkMaster();
    }

    /**
     * @test
     */
    public function sendSignalToMaster()
    {
        $container = $this->makeForkContainer();
        $masterPid = $container->forkMaster();

        $container->sendSignalToMaster(SIGTERM);

        // pcntl_wait with WUNTRACED returns `-1` if process has already terminated.
        $status = null;
        $this->assertSame(-1, pcntl_waitpid($masterPid, $status, WUNTRACED));
    }
}
