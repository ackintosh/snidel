<?php
class Snidel_Data
{
    /** @var int */
    private $pid;

    /** @var Snidel_SharedMemory */
    private $shm;

    /**
     * @param   int     $pid
     */
    public function __construct($pid)
    {
        $this->pid = $pid;
        $this->shm = new Snidel_SharedMemory($pid);
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
        try {
            $this->shm->open(strlen($serializedData));
        } catch (RuntimeException $e) {
            throw $e;
        }

        try {
            $this->shm->write($serializedData);
        } catch (RuntimeException $e) {
            throw $e;
        }

        $this->shm->close();
    }

    /**
     * read data and delete shared memory
     *
     * @return  mix
     * @throws  RuntimeException
     */
    public function readAndDelete()
    {
        try {
            $data = $this->read();
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
        try {
            $this->shm->open();
            $data = $this->shm->read();
        } catch (RuntimeException $e) {
            throw $e;
        }

        $this->shm->close();
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
        try {
            $this->shm->open();
        } catch (RuntimeException $e) {
            return;
        }

        try {
            $this->shm->delete();
        } catch (RuntimeException $e) {
            throw $e;
        }

        $this->shm->close($removeTmpFile = true);
    }
}
