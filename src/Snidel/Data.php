<?php
class Snidel_Data
{
    /** @var int */
    private $pid;

    /**
     * @param   int     $pid
     */
    public function __construct($pid)
    {
        $this->pid = $pid;
    }

    /**
     * write data
     *
     * @param   mix     $data
     * @return  void
     * @throws  RuntimeException
     */
    public function write($data)
    {
        $serializedData = serialize(array(
            'pid'   => $this->pid,
            'data'  => $data,
        ));
        $s = shmop_open($this->genKey(), 'n', 0666, strlen($serializedData));
        if ($s === false) {
            throw new RuntimeException('could not open shared memory');
        }

        $writtenSize = shmop_write($s, $serializedData, 0);
        if ($writtenSize === false) {
            shmop_delete($s);
            shmop_close($s);
            throw new RuntimeException('could not write the data to shared memory');
        }

        shmop_close($s);
    }

    /**
     * read data and delete shared memory
     *
     * @return  mix
     * @throws  RuntimeException
     */
    public function readAndDelete()
    {
        $data = $this->read();

        try {
            $this->delete();
        } catch (RuntimeException $e) {
            throw $e;
        }

        return $data;
    }

    /**
     * read data
     *
     * @return  array
     * @throws  RuntimeException
     */
    public function read()
    {
        $s = shmop_open($this->genKey(), 'a', 0, 0);
        if ($s === false) {
            throw new RuntimeException('could not open shared memory');
        }
        $data = shmop_read($s, 0, shmop_size($s));
        shmop_close($s);

        $unserialized = unserialize($data);
        return $unserialized['data'];
    }

    /**
     * delete shared memory
     *
     * @return  void
     * @throws  RuntimeException
     */
    public function delete()
    {
        $s = @shmop_open($this->genKey(), 'a', 0, 0);
        if ($s === false) {
            return;
        }

        if (!shmop_delete($s)) {
            throw new RuntimeException('could not delete shared memory');
        }
        shmop_close($s);
        unlink('/tmp/' . sha1($this->pid));
    }

    /**
     * generate IPC key
     *
     * @return  int
     */
    private function genKey()
    {
        $pathname = '/tmp/' . sha1($this->pid);
        if (!file_exists($pathname)) {
            touch($pathname);
        }

        return ftok($pathname, 'S');
    }
}
