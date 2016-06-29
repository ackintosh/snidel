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
        $container = new Container(getmypid(), new Log(getmypid()));
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

        $pcntl = $this->getMockBuilder(Pcntl::class)
            ->setMethods(array('fork'))
            ->getMock();

        $pcntl->expects($this->once())
            ->method('fork')
            ->will($this->returnValue(-1));

        $container = $this->makeForkContainer();
        $prop = new \ReflectionProperty($container, 'pcntl');
        $prop->setAccessible(true);
        $prop->setValue($container, $pcntl);

        $method = new \ReflectionMethod(Container::class, 'fork');
        $method->setAccessible(true);

        $method->invoke($container);
    }
}
