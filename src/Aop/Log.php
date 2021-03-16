<?php
namespace chj\Swoole\Aop;

class Log
{

    /**
     * log message
     *
     * @param string $message
     */
    public function save($message)
    {
        echo $message, "\n";
    }

}