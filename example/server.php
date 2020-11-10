<?php

include '../src/function.php';
function autoload( $class ){
    $includePath = str_replace( '\\', DS, $class );
    $includePath = str_replace( 'chj/Swoole', 'src', $includePath );
    $targetFile = ROOT.$includePath.'.php';
    require_once( $targetFile );
}
spl_autoload_register( 'autoload' );

chj\Swoole\Library\Router::group(['namespace' => 'Application\Controller'], function ($route) {
    $route::add('login','Account@login');
    $route::add('login2','Account@login2');
    $route::add('getUserByName', function ($name,$tes) {
        return 'name: ' . $name;
    });
    $route::add('getUserByName2', function ($name) {
        return 'name: ' . $name;
    });
});

$gmu = new chj\Swoole\Coroutine\Server\Server();
// 开启一些配置项
$gmu->initSetting( array(
    'http' => array(
        'host' => '0.0.0.0',
        'port' => 8090,
    ),
    'tcp' => array(
        'host' => '0.0.0.0',
        'port' => 9501,
    ),
    'custom' => array(
        'tcpPack' => 'length',    // 1.eof，eof拆包 2.length，length拆包
    ),
    // 服务注册
    'serviceRegisterSetting' => array(
        'host' => '127.0.0.1',
        'port' => 6379,
        'serviceName' => 'account-service',
    ),
) );
$gmu->run();