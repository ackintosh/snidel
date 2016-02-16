<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\DataRepository;

class DataRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function load()
    {
        $dataRepository = new DataRepository();
        $this->assertInstanceOf('Ackintosh\\Snidel\\Data', $dataRepository->load(getmypid()));
    }
}
