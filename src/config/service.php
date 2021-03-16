<?php

return [
    'http' => [
        'host' => '0.0.0.0',
        'port' => 8090,
    ],
    'tcp' => [
        'host' => '0.0.0.0',
        'port' => 9501,
    ],
    'custom' => [
        'tcpPack' => 'length',    // 1.eof，eof拆包 2.length，length拆包
    ],
    // 服务注册
    'serviceRegisterSetting' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'serviceName' => 'account-service',
    ],
];