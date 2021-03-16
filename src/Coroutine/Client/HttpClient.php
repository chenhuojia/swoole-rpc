<?php
/**
 * tcp协程客户端
 */
namespace chj\Swoole\Coroutine\Client;

class HttpClient extends Client
{

    protected $config = [
        'host'  =>  '0.0.0.0',
        'port'  =>  '9501'
    ];

    protected $setting = [
        'open_length_check' => 1,
        'package_length_type' => 'N',
        'package_length_offset' => 0,
        'package_body_offset' => 4,
    ];


}