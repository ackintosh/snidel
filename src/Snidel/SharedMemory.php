<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\IpcKey;
use Ackintosh\Snidel\Exception\SharedMemoryControlException;

class SharedMemory
{
    /** @var int **/
    private $pid;

    /** @var int **/
    private $key;

    /** @var int **/
    private $segmentId;

    /** @var \Ackintosh\Snidel\IpcKey */
    private $ipcKey;

    /**
     * @param   int     $pid
     */
    public function __construct($pid)
    {
        $this->pid = $pid;
        $this->ipcKey = new IpcKey($pid, 'snidel_shm_');
        $this->key = $this->ipcKey->generate();
    }

    /**
     * create or open shared memory block
     *
     * @param   int     $length
     * @throws  \Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function open($length = 0)
    {
        $flags  = ($length === 0) ? 'a' : 'n';
        $mode   = ($length === 0) ? 0 : 0666;
        $this->segmentId = @shmop_open($this->key, $flags, $mode, $length);
        if ($this->segmentId === false) {
            throw new SharedMemoryControlException('could not open shared memory');
        }
    }

    /**
     * write data into shared memory block
     *
     * @param   string  $data
     * @throws  \Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function write($data)
    {
        $writtenSize = @shmop_write($this->segmentId, $data, 0);
        if ($writtenSize === false) {
            throw new SharedMemoryControlException('could not write the data to shared memory');
        }
    }

    /**
     * read data from shared memory block
     *
     * @return string
     * @throws  \Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function read()
    {
        $data = @shmop_read($this->segmentId, 0, shmop_size($this->segmentId));
        if ($data === false) {
            throw new SharedMemoryControlException('could not read the data to shared memory');
        }

        return $data;
    }

    /**
     * delete shared memory block
     *
     * @throws  \Ackintosh\Snidel\Exception\SharedMemoryControlException
     */
    public function delete()
    {
        if ($this->segmentId && !@shmop_delete($this->segmentId)) {
            throw new SharedMemoryControlException('could not delete the data to shared memory');
        }
    }

    /**
     * cloase shared memory block
     *
     * @param   bool    $deleteIpcKey
     */
    public function close($deleteIpcKey = false)
    {
        if ($this->segmentId) {
            shmop_close($this->segmentId);
        }
        if ($deleteIpcKey) {
            $this->ipcKey->delete();
        }
    }

    /**
     * check whether shared memory exists
     *
     * @return  bool
     */
    public function exists()
    {
        $ret = @shmop_open($this->key, 'a', 0, 0);
        return $ret !== false;
    }
}
