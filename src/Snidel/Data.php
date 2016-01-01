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
     * @param   mixed     $data
     * @return  void
     * @throws  Snidel_Exception_SharedMemoryControlException
     */
    public function write($data)
    {
        $serializedData = serialize(array(
            'pid'   => $this->pid,
            'data'  => $data,
        ));
        try {
            $this->shm->open(strlen($serializedData));
        } catch (Snidel_Exception_SharedMemoryControlException $e) {
            throw $e;
        }

        try {
            $this->shm->write($serializedData);
        } catch (Snidel_Exception_SharedMemoryControlException $e) {
            throw $e;
        }

        $this->shm->close();
    }

    /**
     * read data and delete shared memory
     *
     * @return  mixed
     * @throws  Snidel_Exception_SharedMemoryControlException
     */
    public function readAndDelete()
    {
        try {
            $data = $this->read();
            $this->delete();
        } catch (Snidel_Exception_SharedMemoryControlException $e) {
            throw $e;
        }

        return $data;
    }

    /**
     * read data
     *
     * @return  array
     * @throws  Snidel_Exception_SharedMemoryControlException
     */
    public function read()
    {
        try {
            $this->shm->open();
            $data = $this->shm->read();
        } catch (Snidel_Exception_SharedMemoryControlException $e) {
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
     * @throws  Snidel_Exception_SharedMemoryControlException
     */
    public function delete()
    {
        try {
            $this->shm->open();
        } catch (Snidel_Exception_SharedMemoryControlException $e) {
            throw $e;
        }

        try {
            $this->shm->delete();
        } catch (Snidel_Exception_SharedMemoryControlException $e) {
            throw $e;
        }

        $this->shm->close($removeTmpFile = true);
    }
}
