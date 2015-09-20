<?php
class Snidel_Data
{
    private $pid;

    public function __construct($pid)
    {
        $this->pid = $pid;
    }

    public function write($data)
    {
        $serializedData = serialize($data);
        $s = shmop_open($this->genKey(), 'n', 0666, strlen($serializedData));
        if ($s === false) {
            exit(1);
        }

        $writtenSize = shmop_write($s, $serializedData, 0);
        if ($writtenSize === false) {
            shmop_delete($s);
            shmop_close($s);
            exit(1);
        }

        shmop_close($s);
    }

    public function read()
    {
        $s = shmop_open($this->genKey(), 'a', 0, 0);
        if ($s === false) {
            exit(1);
        }

        $data = shmop_read($s, 0, shmop_size($s));
        shmop_close($s);

        return unserialize($data);
    }

    public function readAndDelete()
    {
        $s = shmop_open($this->genKey(), 'a', 0, 0);
        if ($s === false) {
            exit(1);
        }

        $data = shmop_read($s, 0, shmop_size($s));
        if (!shmop_delete($s)) {
            die('failed to delete : ' . $s);
        }
        shmop_close($s);

        return unserialize($data);

    }

    private function genKey()
    {
        $pathname = '/tmp/' . sha1($this->pid);
        if (!file_exists($pathname)) {
            touch($pathname);
        }

        return ftok($pathname, 'S');
    }
}
