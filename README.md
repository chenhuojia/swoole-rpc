
# HJRPC
## 基于swoole协程封装的一个rpc服务,路由注册

##安装
- composer require chj/swoole-rpc

##使用

### 开启服务
```php
include  '../vendor/autoload.php';
/**
* 开启TCP(9501)&HTTP(8090)服务
**/
$server = new chj\Swoole\Coroutine\Server\Server();
//配置
$server->initSetting([
        'http' =>[   
            'host' => '0.0.0.0',
            'port' => 8090,
        ],
        'tcp' =>[ 
            'host' => '0.0.0.0',
            'port' => 9501,
        ],
        'custom' =>[ 
            'tcpPack' => 'length',    // 1.eof，eof拆包 2.length，length拆包
        ],
        // 服务注册
        'serviceRegisterSetting' =>[
            'host' => '127.0.0.1',
            'port' => 6379,
            'serviceName' => 'account-service',
        ],
]);
//运行服务
$server->run();
```

### 客户端 
连接服务
```php
include  '../vendor/autoload.php';

$client = \chj\Swoole\Coroutine\Client\RpcClient::getInstance(['host'=>'0.0.0.0','port'=>'6667']);

$data = $client->login('chj','login');

print_r($data);
```
### 注册路由
要在服务启动前注册路由
````php
include  '../vendor/autoload.php';
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
````
