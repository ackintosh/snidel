<?php
class Snidel_DataTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function constructorSetsPid()
    {
        $data = new Snidel_Data(1234);
        $ref = new ReflectionProperty($data, 'pid');
        $ref->setAccessible(true);
        $this->assertSame($ref->getValue($data), 1234);
    }

    /**
     * @test
     */
    public function writeAndRead()
    {
        $data = new Snidel_Data(getmypid());
        $data->write('foo');
        $this->assertSame($data->readAndDelete(), 'foo');
    }
}
