<?php
if( 'cli' !== php_sapi_name() ){
    exit( '服务只能运行在cli sapi模式下'.PHP_EOL );
}

if( !extension_loaded('swoole') ){
    exit( '请安装swoole扩展'.PHP_EOL );
}

// 定义系统常量
define( 'DS', DIRECTORY_SEPARATOR );
define( 'ROOT', __DIR__.DS );

function autoload( $class ){
    $includePath = str_replace( '\\', DS, $class );
    $targetFile = ROOT.$includePath.'.php';
    require_once( $targetFile );
}
spl_autoload_register( 'autoload' );
// 继承Core父类
class Gmu extends chj\PRC\Coroutine\Server\Server {

    // 具体业务逻辑
    public function process( $server, $param ){
        var_dump($param).PHP_EOL;
        // 将param抛给model中的method，并获得到处理完后的数据
        $targetModel = '\Application\\Controller\\'.ucfirst( $param['param']['model'] );
        $targetModel = new $targetModel;
        $targetConfig['param'] = $param['param']['param'];
        $sendData = call_user_func_array( array( $targetModel, $param['param']['method'] ), array( $targetConfig ) );
        return $sendData;

    }

}
$gmu = new Gmu();
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