<?php
declare(strict_types=1);
namespace chj\Swoole\Coroutine\Client;
use chj\Swoole\Library\Packet;

class Client
{

    /**
     * config
     * @var array
     */
    protected $config = [];

    /**
     * setting
     * @var array
     */
    protected $setting = [];

    /**
     * 实例化
     * @param array $config
     * @return RpcClient
     */
    public static function getInstance(array $config)
    {
        return new self($config);
    }

    private function __construct(array $config)
    {
        $config && $this->config = $config;
    }


    /**
     * 创建tcp客户端连接
     * @param $send
     * @return array
     */
    protected function create($send)
    {
        $result = [];
        if( 'cli' !== php_sapi_name() ){
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            $client->set($this->setting);
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
                $client->set($this->setting);
                if (! $client->connect($this->config['host'], $this->config['port'], 0.5))
                {
                    throw new \Exception('客户端连接失败：'. $client->errCode);
                }
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


}