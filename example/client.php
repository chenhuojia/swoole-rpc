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

$client = \chj\SwooleRpc\Coroutine\Client\RpcClient::getInstance(['host'=>'0.0.0.0','port'=>'6667']);
$data = $client->login('chj','login');
print_r($data);