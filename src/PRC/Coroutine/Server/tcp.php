<?php
require_once './router.php';

class TcpServer
{
    /**
     * 版本号
     */
    const VERSION = 1001;

    private $server  = [];
    private $connect = [];
    protected $_headers = []; //保存头
    protected $_buffer  = []; //buffer区
    protected $errCode;
    protected $errMsg;
    /**
     * 客户端环境变量
     * @var array
     */
    static $clientEnv;
    static $stop = false;
    /**
     * 请求头
     * @var array
     */
    static $requestHeader;

    public $packet_maxlen       = 2465792; //2M默认最大长度
    protected $buffer_maxlen    = 10240; //最大待处理区排队长度, 超过后将丢弃最早入队数据
    protected $buffer_clear_num = 128; //超过最大长度后，清理100个数据

    const ERR_HEADER            = 9001;   //错误的包头
    const ERR_TOOBIG            = 9002;   //请求包体长度超过允许的范围
    const ERR_SERVER_BUSY       = 9003;   //服务器繁忙，超过处理能力
    const ERR_UNPACK            = 9204;   //解包失败
    const ERR_PARAMS            = 9205;   //参数错误
    const ERR_NOFUNC            = 9206;   //函数不存在
    const ERR_CALL              = 9207;   //执行错误
    const ERR_ACCESS_DENY       = 9208;   //访问被拒绝，客户端主机未被授权
    const ERR_USER              = 9209;   //用户名密码错误

    const HEADER_SIZE           = 16;
    const HEADER_STRUCT         = "Nlength/Ntype/Nuid/Nserid";
    const HEADER_PACK           = "NNNN";

    const DECODE_PHP            = 1;   //使用PHP的serialize打包
    const DECODE_JSON           = 2;   //使用json_encode打包
    const DECODE_MSGPACK        = 3;   //使用msgpack打包
    const DECODE_SWOOLE         = 4;   //使用swoole_serialize打包
    const DECODE_GZIP           = 128; //启用GZIP压缩

    const ALLOW_IP              = 1;
    const ALLOW_USER            = 2;

    protected $appNamespaces    = array(); //应用程序命名空间
    protected $ipWhiteList      = array(); //IP白名单
    protected $userList         = array(); //用户列表

    protected $verifyIp         = false;
    protected $verifyUser       = false;

    static $calls = [];   //
    static $names = [];   //

    protected $config = [];

    public function __construct($workerNum = 1,$host='0.0.0.0',$port='9231',$ssl = false,$reuse_port = true)
    {
        $pool = new Swoole\Process\Pool($workerNum);
        //让每个OnWorkerStart回调都自动创建一个协程
        $pool->set(['enable_coroutine' => true]);
        $pool->on('workerStart', function ($pool, $id)use($host,$port,$ssl,$reuse_port) {
            $this->server[$id] = new Swoole\Coroutine\Server($host,$port,$ssl,$reuse_port);
            //收到15信号关闭服务
            Swoole\Process::signal(SIGTERM, function () use ($id) {
                $this->server[$id]->shutdown();
            });
            $this->server[$id]->handle(function (Swoole\Coroutine\Server\Connection $connection)use($id){
                $this->connect[$id] = $connection;
                while (true) {
                    //接收数据
                    $data =  $this->connect[$id]->recv();
                    if (empty($data)) {
                        $this->connect[$id]->close();
                        break;
                    }
                    //$this->connect[$id]->send('hellow');
                    $this->recv($id,$data);
                    //发送数据
                    // \Co::sleep(1);
                }
            });
            $this->server[$id]->start();
        });
        $pool->start();
    }

    public function getInstance()
    {

    }


    public function close($fd)
    {
       return $this->connect[$fd]->close();
    }
    public function start($fd)
    {
        return $this->server[$fd]->start();
    }

    public function log($log)
    {
        return var_dump($log).PHP_EOL;
    }

    public function sendErrorMessage($fd,$errno)
    {
        return $this->connect[$fd]->send(self::encode(array('errno' => $errno), $this->_headers[$fd]['type']));
    }

    public function recv($fd,$data)
    {
        $this->log($data);
        if (!isset($this->_buffer[$fd]) or $this->_buffer[$fd] === '')
        {
            //超过buffer区的最大长度了
            if (count($this->_buffer) >= $this->buffer_maxlen)
            {
                $n = 0;
                foreach ($this->_buffer as $k => $v)
                {
                    $this->close($k);
                    $n++;
                    //清理完毕
                    if ($n >= $this->buffer_clear_num)
                    {
                        break;
                    }
                }
                $this->log("clear $n buffer");
            }
            //解析包头
            $header = unpack(self::HEADER_STRUCT, substr($data, 0, self::HEADER_SIZE));
            //错误的包头
            if ($header === false)
            {
                $this->close($fd);
                $this->log("错误的包头");
            }
            $header['fd'] = $fd;
            $this->_headers[$fd] = $header;
            //长度错误
            if ($header['length'] - self::HEADER_SIZE > $this->packet_maxlen or strlen($data) > $this->packet_maxlen)
            {
                $this->log("长度错误");
                return $this->sendErrorMessage($fd, self::ERR_TOOBIG);
            }
            //加入缓存区
            $this->_buffer[$fd] = substr($data, self::HEADER_SIZE);
        }
        else
        {
            $this->_buffer[$fd] .= $data;
        }
        //长度不足
        if (strlen($this->_buffer[$fd]) < $this->_headers[$fd]['length'])
        {
            $this->log("长度不足");
            return true;
        }
        //数据解包
        $request = self::decode($this->_buffer[$fd], $this->_headers[$fd]['type']);
        if ($request === false)
        {
            $this->sendErrorMessage($fd, self::ERR_UNPACK);
        }
        //执行远程调用
        else
        {
            //当前请求的头
            self::$requestHeader = $_header = $this->_headers[$fd];
            //调用端环境变量
            if (!empty($request['env']))
            {
                self::$clientEnv = $request['env'];
            }
            //socket信息

            self::$clientEnv['_socket'] = $this->server[$fd]->connection_info($_header['fd']);
            $response = $this->call($request, $_header);
            //发送响应
            $ret = $this->connect[$fd]->send(self::encode($response, $_header['type'], $_header['uid'], $_header['serid']));
            if ($ret === false)
            {
                trigger_error("SendToClient failed. code=".$this->server[$fd]->getLastError()." params=".var_export($request, true)."\nheaders=".var_export($_header, true), E_USER_WARNING);
            }
            //退出进程
            if (self::$stop)
            {
                exit(0);
            }
        }
        //清理缓存
        unset($this->_buffer[$fd], $this->_headers[$fd]);
        return true;
    }

    /**
     * 解包数据
     * @param string $data
     * @param int $unseralize_type
     * @return string
     */
    static function decode($data, $unseralize_type = self::DECODE_PHP)
    {
        if ($unseralize_type & self::DECODE_GZIP)
        {
            $unseralize_type &= ~self::DECODE_GZIP;
            $data = gzdecode($data);
        }
        switch ($unseralize_type)
        {
            case self::DECODE_JSON:
                return json_decode($data, true);
            case self::DECODE_SWOOLE:
                return \swoole_serialize::unpack($data);
            case self::DECODE_PHP;
            default:
                return unserialize($data);
        }
    }
    /**
     * 打包数据
     * @param $data
     * @param $type
     * @param $uid
     * @param $serid
     * @return string
     */
    static function encode($data, $type = self::DECODE_PHP, $uid = 0, $serid = 0)
    {
        //启用压缩
        if ($type & self::DECODE_GZIP)
        {
            $_type = $type & ~self::DECODE_GZIP;
            $gzip_compress = true;
        }
        else
        {
            $gzip_compress = false;
            $_type = $type;
        }
        switch($_type)
        {
            case self::DECODE_JSON:
                $body = json_encode($data);
                break;
            case self::DECODE_SWOOLE:
                $body = \swoole_serialize::pack($data);
                break;
            case self::DECODE_PHP:
            default:
                $body = serialize($data);
                break;
        }
        if ($gzip_compress)
        {
            $body = gzencode($body);
        }
        return pack(self::HEADER_PACK, strlen($body), $type, $uid, $serid) . $body;
    }



    /**
     * 调用远程函数
     * @param $request
     * @return array
     */
    protected function call($request, $header)
    {
        if (empty($request['call']))
        {
            return array('errno' => self::ERR_PARAMS);
        }
        /**
         * 侦测服务器是否存活
         */
        if ($request['call'] === 'PING')
        {
            return array('errno' => 0, 'data' => 'PONG');
        }
        //验证客户端IP是否被允许访问
        if ($this->verifyIp)
        {
            if (!$this->verifyIp(self::$clientEnv['_socket']['remote_ip']))
            {
                return array('errno' => self::ERR_ACCESS_DENY);
            }
        }
        //验证密码是否正确
        if ($this->verifyUser)
        {
            if (empty(self::$clientEnv['user']) or empty(self::$clientEnv['password']))
            {
                fail:
                return array('errno' => self::ERR_USER);
            }
            if (!$this->verifyUser(self::$clientEnv['user'], self::$clientEnv['password']))
            {
                goto fail;
            }
        }
        //函数不存在
        if (!is_callable($request['call']))
        {
            return array('errno' => self::ERR_NOFUNC);
        }
        //前置方法
        if (method_exists($this, 'beforeRequest'))
        {
            $this->beforeRequest($request);
        }
        //调用接口方法
        $ret = call_user_func_array($request['call'], $request['params']);
        //后置方法
        if (method_exists($this, 'afterRequest'))
        {
            $this->afterRequest($ret);
        }
        //禁止接口返回NULL，客户端得到NULL时认为RPC调用失败
        if ($ret === NULL)
        {
            return array('errno' => self::ERR_CALL);
        }
        return array('errno' => 0, 'data' => $ret);
    }

    /**
     * 验证IP
     * @param $ip
     * @return bool
     */
    protected function verifyIp($ip)
    {
        return isset($this->ipWhiteList[$ip]);
    }

    /**
     * 验证用户名密码
     * @param $user
     * @param $password
     * @return bool
     */
    protected function verifyUser($user, $password)
    {
        if (!isset($this->userList[$user]))
        {
            return false;
        }
        if ($this->userList[$user] != $password)
        {
            return false;
        }
        return true;
    }
}

//Co\run(function () {
    new TcpServer();
//});

