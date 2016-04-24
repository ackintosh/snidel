<?php
namespace Ackintosh\Snidel;

use Ackintosh\Snidel\IpcKey;
use Opis\Closure\SerializableClosure;

class TaskQueue
{
    const TASK_MAX_SIZE = 1024;

    private $queuedCount = 0;
    private $dequeuedCount = 0;

    public function __construct($ownerPid)
    {
        $this->ownerPid = $ownerPid;
        $this->ipcKey = new IpcKey($ownerPid, 'snidel_task_' . uniqid((string) mt_rand(1, 100), true));
        $this->id = msg_get_queue($this->ipcKey->generate());
    }

    /**
     * @param   callable            $callable
     * @param   array               $args
     * @param   string              $tag
     * @return  void
     * @throws  RuntimeException
     */
    public function enqueue($callable, $args = array(), $tag = null)
    {
        $this->queuedCount++;
        if ($this->isClosure($callable)) {
            $serializedCallable = new SerializableClosure($callable);
        } else {
            $serializedCallable = serialize($callable);
        }

        $data = array(
            'callable'  => $serializedCallable,
            'args'      => $args,
            'tag'       => $tag,
        );

        if (!msg_send($this->id, 1, serialize($data))) {
            throw new RuntimeException('failed to enqueue task.');
        }
    }

    public function dequeue()
    {
        $this->dequeuedCount++;
        $msgtype = $message = null;
        $success = msg_receive($this->id, 1, $msgtype, self::TASK_MAX_SIZE, $message);

        if (!$success) {
            throw new \RuntimeException('failed to dequeue task');
        }

        $data = unserialize($message);
        if ($this->isClosure($data['callable'])) {
            $data['callable'] = $data['callable']->getClosure();
        } else {
            $data['callable'] = unserialize($data['callable']);
        }
        return $data;
    }

    public function queuedCount()
    {
        return $this->queuedCount;
    }

    public function dequeuedCount()
    {
        return $this->dequeuedCount;
    }

    private function isClosure($callable)
    {
        return is_object($callable) && is_callable($callable);
    }

    public function __destruct()
    {
        if ($this->ipcKey->isOwner(getmypid())) {
            $this->ipcKey->delete();
            return msg_remove_queue($this->id);
        }
    }// @codeCoverageIgnore
}
