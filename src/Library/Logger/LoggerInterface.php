<?php
namespace chj\Swoole\Library\Logger;

interface LoggerInterface
{

    /**
     * @param string $message
     * @param array $context
     * @return mixed
     */
    public static function info(string $message, array $context = []);

    /**
     * @param string $message
     * @param array $context
     * @return mixed
     */
    public static function debug(string $message, array $context = []);

    /**
     * @param string $message
     * @param array $context
     * @return mixed
     */
    public static function error(string $message, array $context = []);

    /**
     * @param string $message
     * @param array $context
     * @return mixed
     */
    public static function warning(string $message, array $context = []);

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     * @return mixed
     */
    public static function log(string $level, string $message, array $context = []);

}