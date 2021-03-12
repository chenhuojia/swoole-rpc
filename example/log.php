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
spl_autoload_register('classAutoLoader');
$logConfig['log_path'] = dirname(__FILE__).'/logs';
$packet = \chj\Swoole\Library\Packet::encode(['host'=>'120.76.174.33','port'=>'6667']);

print_r($packet);