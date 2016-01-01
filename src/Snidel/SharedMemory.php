<?php
class Snidel_SharedMemory
{
    /** @int **/
    private $pid;

    /** @int **/
    private $key;

    /** @int **/
    private $segmentId;

    /** @const string **/
    const TMP_FILE_PREFIX = 'snidel_shm_';

    /**
     * @param   int     $pid
     */
    public function __construct($pid)
    {
        $this->pid = $pid;
        $this->key = $this->generateKey($pid);
    }

    /**
     * create or open shared memory block
     *
     * @param   int     $length
     * @throws  Snidel_Exception_SharedMemoryControlException
     */
    public function open($length = 0)
    {
        $flags  = ($length === 0) ? 'a' : 'n';
        $mode   = ($length === 0) ? 0 : 0666;
        $this->segmentId = @shmop_open($this->key, $flags, $mode, $length);
        if ($this->segmentId === false) {
            throw new Snidel_Exception_SharedMemoryControlException('could not open shared memory');
        }
    }

    /**
     * write data into shared memory block
     *
     * @param   string  $data
     * @throws  Snidel_Exception_SharedMemoryControlException
     */
    public function write($data)
    {
        $writtenSize = @shmop_write($this->segmentId, $data, 0);
        if ($writtenSize === false) {
            throw new Snidel_Exception_SharedMemoryControlException('could not write the data to shared memory');
        }
    }

    /**
     * read data from shared memory block
     *
     * @return string
     * @throws  Snidel_Exception_SharedMemoryControlException
     */
    public function read()
    {
        $data = @shmop_read($this->segmentId, 0, shmop_size($this->segmentId));
        if ($data === false) {
            throw new Snidel_Exception_SharedMemoryControlException('could not read the data to shared memory');
        }

        return $data;
    }

    /**
     * delete shared memory block
     *
     * @throws  Snidel_Exception_SharedMemoryControlException
     */
    public function delete()
    {
        if ($this->segmentId && !@shmop_delete($this->segmentId)) {
            throw new Snidel_Exception_SharedMemoryControlException('could not delete the data to shared memory');
        }
    }

    /**
     * cloase shared memory block
     *
     * @param   bool    $removeTmpFile
     */
    public function close($removeTmpFile = false)
    {
        if ($this->segmentId) {
            shmop_close($this->segmentId);
        }
        if ($removeTmpFile) {
            unlink('/tmp/' . self::TMP_FILE_PREFIX . sha1($this->pid));
        }
    }

    /**
     * generate IPC key
     *
     * @return  int
     */
    private function generateKey($pid)
    {
        $pathname = '/tmp/' . self::TMP_FILE_PREFIX . sha1($pid);
        if (!file_exists($pathname)) {
            touch($pathname);
        }

        return ftok($pathname, 'S');
    }
}
