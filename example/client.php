<?php
include '../src/function.php';
function autoload( $class ){
    $includePath = str_replace( '\\', DS, $class );
    $includePath = str_replace( 'chj/Swoole', 'src', $includePath );
    $targetFile = ROOT.$includePath.'.php';
    require_once( $targetFile );
}
spl_autoload_register( 'autoload' );

$client = \chj\Swoole\Coroutine\Client\RpcClient::getInstance(['host'=>'120.76.174.33','port'=>'9501']);
$data = $client->login('chj','login');
print_r($data);