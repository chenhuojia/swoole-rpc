<?php

class TcpClient
{

    private $client;
    const OK = 0;

    /**
     * 版本号
     */
    const VERSION = 1001;

    /**
     * Server的实例列表
     * @var array
     */
    protected $servers = array();

    protected $requestIndex = 0;

    protected $env = array();

    /**
     * 连接到服务器
     * @var array
     */
    protected $connections = array();

    protected $waitList = array();
    protected $timeout = 0.5;
    protected $packet_maxlen = 2097152;   //最大不超过2M的数据包

    /**
     * 启用长连接
     * @var bool
     */
    protected $keepConnection = false;

    protected $haveSwoole = false;
    protected $haveSockets = false;

    protected static $_instances = array();

    protected $encode_gzip = false;
    protected $encode_type = 1;

    protected $user;
    protected $password;

    private $keepSocket = false;    //让整个对象保持同一个socket，不再重新分配
    private $keepSocketServer = array();    //对象保持同一个socket的服务器信息

    public function __construct($host='0.0.0.0',$port='9231')
    {
        $client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
        if (!$client->connect($host, $port, 0.5))
        {
            echo "connect failed. Error: {$client->errCode}\n";
        }
        while (true)
        {
            $data = $client->recv();
            var_dump($data).PHP_EOL;
            if(!feof(STDIN)) {
                $line = fread(STDIN, 1024);
                $client->send($line);
            }
            //\Co::sleep(1);
        }
        $client->close();

    }


}

Co::set(['hook_flags'=> SWOOLE_HOOK_ALL]);
Co\run(function (){
    (new TcpClient());
});