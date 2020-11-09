<?php
if( 'cli' !== php_sapi_name() ){
    exit( '服务只能运行在cli sapi模式下'.PHP_EOL );
}

if( !extension_loaded('swoole') ){
    exit( '请安装swoole扩展'.PHP_EOL );
}

// 定义系统常量
define( 'DS', DIRECTORY_SEPARATOR );
define( 'ROOT', dirname(__DIR__).DS );
define('HaveGenerator', class_exists("\\Generator", false));

include '../src/function.php';
function autoload( $class ){
    $includePath = str_replace( '\\', DS, $class );
    $includePath = str_replace( 'chj/SwooleRpc', 'src', $includePath );
    $targetFile = ROOT.$includePath.'.php';
    require_once( $targetFile );
}
spl_autoload_register( 'autoload' );

chj\SwooleRpc\Library\Router::group(['namespace' => 'Application\Controller'], function ($route) {
    $route::add('login','Account@login');
    $route::add('login2','Account@login2');
    $route::add('getUserByName', function ($name,$tes) {
        return 'name: ' . $name;
    });
    $route::add('getUserByName2', function ($name) {
        return 'name: ' . $name;
    });
});

$gmu = new chj\SwooleRpc\Coroutine\Server\Server();
// 开启一些配置项
$gmu->initSetting( array(
    'http' => array(
        'host' => '0.0.0.0',
        'port' => 6666,
    ),
    'tcp' => array(
        'host' => '0.0.0.0',
        'port' => 6667,
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