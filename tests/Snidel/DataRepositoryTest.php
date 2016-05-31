<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\DataRepository;
use Ackintosh\Snidel\SharedMemory;
use Ackintosh\Snidel\Result\Result;
use Ackintosh\Snidel\Task\Task;
use Ackintosh\Snidel\IpcKey;

/**
 * @runTestsInSeparateProcesses
 */
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

    /**
     * @test
     */
    public function deleteAll()
    {
        $dataRepository = new DataRepository();
        $data = $dataRepository->load(getmypid());
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
        $data->write($result);

        $shm = new SharedMemory(getmypid());
        $this->assertTrue($shm->exists());

        $dataRepository->deleteAll();
        $this->assertFalse($shm->exists());
    }

    /**
     * @test
     * @expectedException \Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function deleteAllThrowsException()
    {
        $dataRepository = new DataRepository();
        $data = $dataRepository->load(getmypid());
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
        $data->write($result);

        require(__DIR__ . '/../shmop_delete.php');
        try {
            $dataRepository->deleteAll();
        } catch (\Ackintosh\Snidel\Exception\SharedMemoryControlException $e) {
            $ipcKey = new IpcKey(getmypid(), 'snidel_shm_');
            \shmop_delete(\shmop_open($ipcKey->generate(), 'a', 0, 0));
            throw $e;
        }
    }
}
