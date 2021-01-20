<?php
/**
 * tcp协程客户端
 */
namespace chj\Swoole\Coroutine\Client;
use chj\Swoole\Library\Packet;

class RpcClient
{

    private static $instance;

    private $config = [
        'host'  =>  '0.0.0.0',
        'port'  =>  '6667'
    ];

    private $setting = [
        'open_length_check' => 1,
        'package_length_type' => 'N',
        'package_length_offset' => 0,
        'package_body_offset' => 4,
    ];

    /**
     * 实例化
     * @param array $config
     * @return RpcClient
     */
    public static function getInstance(array $config)
    {
        return new self($config);
    }

    public function __construct(array $config)
    {
        $config && $this->config = $config;

    }

    /**
     * 方法调用
     * @param $name
     * @param array $arguments
     * @return array
     */
    public function __call($name, array $arguments)
    {
        // TODO: Implement __call() method.
        $send['requestId'] =  time();
        $send['method'] = $name;
        $send['param'] = $arguments;
        $send['type'] = 'SW';
        //$result = [];
        return $this->create($send);
    }

    /**
     * 创建tcp客户端连接
     * @param $send
     * @return array
     */
    private function create($send)
    {
        $result = [];
        if( 'cli' !== php_sapi_name() ){
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if (!$client->connect($this->config['host'], $this->config['port'], -1)) {
                throw new \Exception('客户端连接失败：'. $client->errCode);
            }
            $send = Packet::encode($send);
            $client->send($send);
            $jsonString =  $client->recv();
            $result = Packet::decode($jsonString);
        }else{
            \Swoole\Runtime::enableCoroutine($flags = SWOOLE_HOOK_ALL);
            \Co\run(function ()use($send,&$result) {
                $client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
                if (! $client->connect($this->config['host'], $this->config['port'], 0.5))
                {
                    throw new \Exception('客户端连接失败：'. $client->errCode);
                }
                $client->set($this->setting);
                $send = Packet::encode($send);
                $client->send($send);
                $jsonString =  $client->recv();
                $result = Packet::decode($jsonString);
                $client->close();
                return $result;
            });
        }
        return $result;
    }
}