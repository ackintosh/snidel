<?php
namespace Ackintosh\Snidel;

/**
 * Wrapper for semaphore functions
 */
class Semaphore
{
    public function getQueue(...$args)
    {
        return call_user_func_array('msg_get_queue', $args);
    }

    public function statQueue(...$args)
    {
        return call_user_func_array('msg_stat_queue', $args);
    }

    public function sendMessage(...$args)
    {
        return call_user_func_array('msg_send', $args);
    }

    public function receiveMessage($queue, $desiredmsgtype, &$msgtype, $maxsize, &$message, $unserialize = false)
    {
        // For using block mode, we don't use `call_user_func`.
        return msg_receive($queue, $desiredmsgtype, $msgtype, $maxsize, $message, $unserialize);
    }

    public function removeQueue(...$args)
    {
        return call_user_func_array('msg_remove_queue', $args);
    }
}