<?php
class Snidel_DataRepositoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function load()
    {
        $dataRepository = new Snidel_DataRepository();
        $this->assertInstanceOf('Snidel_Data', $dataRepository->load(getmypid()));
    }
}
