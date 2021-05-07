<?php
include dirname(dirname(__FILE__)).'/src/function.php';
if(!function_exists('classAutoLoader')){
    function classAutoLoader($class){
        $class = str_replace( '\\', DS, $class );
        $class = str_replace( 'chj\Swoole', 'src', $class );
        $class = str_replace( 'chj/Swoole', 'src', $class );
        $class = ROOT.$class;
        $classFile = $class.'.php';
        if(is_file($classFile)&&!class_exists($class)) include $classFile;
    }
}
spl_autoload_register( 'classAutoLoader' );

$client = \chj\Swoole\Coroutine\Client\HttpClient::getInstance(['host'=>'127.0.0.1','port'=>6667]);
$action = 'sms/send';
$data = $client->$action(['platform'=>'zcg','mobile'=>'13622742951']);
print_r($data);