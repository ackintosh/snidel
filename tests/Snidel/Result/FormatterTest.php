<?php
use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Result\Formatter;
use Ackintosh\Snidel\Task\Task;

class ResultFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function minifyAndSerialize()
    {
        $result = new Result();
        $result->setTask(
            new Task(
                function () {
                    return 'foo';
                },
                null,
                null
                )
            );
        $minified = Formatter::minifyAndSerialize($result);

        $this->assertTrue(is_string($minified));
    }
}
