<?php
declare(strict_types=1);
/**
 * tcp协程客户端
 */
namespace chj\Swoole\Coroutine\Client;

class RpcClient extends Client
{

    protected $config = [
        'host'  =>  '0.0.0.0',
        'port'  =>  6667
    ];

    protected $setting = [
        'open_length_check' => 1,
        'package_length_type' => 'N',
        'package_length_offset' => 0,
        'package_body_offset' => 4,
    ];

}
