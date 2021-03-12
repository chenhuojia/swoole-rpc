<?php
namespace chj\Swoole\Library\Logger;

class Logger implements LoggerInterface
{

    /**
     * 普通级别
     * @param string $message
     * @param array $context
     * @return bool|mixed
     */
    public static function info(string $message, array $context = [])
    {
        // TODO: Implement info() method.
        return self::log(LogLevel::INFO, $message, $context);
    }

    /**
     * 调试级别
     * @param string $message
     * @param array $context
     * @return bool|mixed
     */
    public static function debug(string $message, array $context = [])
    {
        // TODO: Implement debug() method.
        return self::log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * 错误级别
     * @param string $message
     * @param array $context
     * @return bool|mixed
     */
    public static function error(string $message, array $context = [])
    {
        // TODO: Implement error() method.
        return self::log(LogLevel::ERROR, $message, $context);
    }


    /**
     * 日志写入
     * @param string $level
     * @param string $message
     * @param array $context
     * @return bool|mixed
     */
    public static function log(string $level, string $message, array $context = [])
    {
        if (!$level || !$message) return false;
        $logMessage = '['.date('Y-m-d H:i:s').'] '.'local.'.strtoupper($level).':  '.$message;
        if ($context)
        {
            $logMessage .= PHP_EOL.var_export($context,true);
        }
        $logMessage .= PHP_EOL;
        return self::write($logMessage);
    }


    /**
     * 警告级别
     * @param string $message
     * @param array $context
     * @return bool|mixed
     */
    public static function warning(string $message, array $context = [])
    {
        // TODO: Implement warning() method.
        return self::log(LogLevel::INFO, $message, $context);
    }


    /**
     * 文件写入
     * @param string $message
     * @return bool
     */
    private static function write(string $message):bool
    {
        if (!$message) return  false;
        global $logConfig;
        $logFile = $logConfig['log_path'].'/chj-rpc.log';
        if ($logConfig['log_channel'] == 'single')
        {
            $logFile = $logConfig['log_path'].'/chj-rpc.log';
        }elseif($logConfig['log_channel'] == 'daily')
        {
            $logFile = $logConfig['log_path'].'/chj-rpc'.date('Y-m-d').'.log';
        }
        try {
            if (!is_dir($logConfig['log_path']))
            {
                @mkdir($logConfig['log_path'],0755,true);
            }
            $fopen = fopen($logFile,'a+');
            fwrite($fopen,(string)$message);
            fclose($fopen);
            return true;
        }catch (\Exception $exception)
        {
            throw $exception;
        }
        return false;
    }

}