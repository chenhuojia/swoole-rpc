<?php

namespace chj\rpc\Coroutine\Server;

use chj\rpc\Coroutine\Library\Packet;

abstract class Server
{
    private $rootPath = '';

    private $directorySep = '';

    /*
     * @desc : http服务器实例
     */
    private $httpServer = null;


    /*
     * @desc : tcp服务器实例
     */
    private $tcpServer = null;

    /*
     * @desc : swoole各个进程角色的title
     */
    private $processTitle = [
        'master' => 'chj-service-master-process',
        'manager' => 'chj-service-manager-process',
        'worker' => 'chj-service-worker-process',
        'tasker' => 'chj-service-tasker-process',
    ];

    /*
     * @desc : swoole tcp服务的配置
     */
    private $tcpSetting = [
        'open_length_check' => true,
        'package_max_length' => 1024 * 1024,
        'package_length_type' => 'N',
        'package_length_offset' => 0,
        'package_body_offset' => 4,
        'open_eof_split' => true,
        'package_eof' => "\r\n",
        'port' => 9801,
        'host' => '0.0.0.0',
        'daemonize'=>false,
    ];

    /*
     * @desc : swoole http服务的配置
     */
    private $httpSetting = [
        'host' => '0.0.0.0',
        'port' => 9802,
        'daemonize'=>false,
    ];

    private $coroutineSetting = [
        'max_coroutine' =>   4,
        'log_level'     => SWOOLE_LOG_TRACE,
        'trace_flags'   => SWOOLE_TRACE_ALL,
    ];

    /*
     * @desc : 用户自定义配置
     */
    private $customSetting = [
            'tcpPack' => 'length',
    ];

    /*
     * @desc : 服务注册
     */
    private $serviceRegisterSetting = [];

    public function initSetting(array $setting)
    {
        $this->rootPath = ROOT;
        $this->tcpSetting['host'] = $this->getLocalIp();
        $this->tcpSetting['log_file'] = $this->rootPath.'Log/CHJ.log';
        $this->tcpSetting['pidFile'] = $this->rootPath.'CHJ.pid';
        $this->tcpSetting['workerPidFile'] = $this->rootPath.'Worker.pid';
        $this->tcpSetting['taskerPidFile'] = $this->rootPath.'Tasker.pid';
        if( isset( $setting['http'] ) ){
            $this->httpSetting = array_merge( $this->httpSetting, $setting['http'] );
        }
        if( isset( $setting['tcp'] ) ){
            $this->tcpSetting = array_merge( $this->tcpSetting, $setting['tcp'] );
        }
        if( isset( $setting['discovery'] ) ){
            $this->discoverySetting = $setting['discovery'];
        }
        if( isset( $setting['custom'] ) ){
            $this->customSetting = array_merge( $this->customSetting, $setting['custom'] );
        }
        if( isset( $setting['serviceRegisterSetting'] ) ){
            $this->serviceRegisterSetting = array_merge( $this->serviceRegisterSetting, $setting['serviceRegisterSetting'] );
        }
        // 查看tcp拆包方式
        if( 'eof' == $this->customSetting['tcpPack'] ){
            $this->tcpSetting['open_eof_check'] = true;
            $this->tcpSetting['package_eof'] = '\r\n';
            $this->tcpSetting['open_eof_split'] = true;
        }	else if( 'length' == $this->customSetting['tcpPack'] ){
            $this->tcpSetting['open_length_check'] = true;
            $this->tcpSetting['package_length_type'] = 'N';
            $this->tcpSetting['package_length_offset'] = 0;
            $this->tcpSetting['package_body_offset'] = 4;
        }
        Packet::setting([
            'tcpPack' => $this->customSetting['tcpPack'],
        ] );
    }

    public function run()
    {
        $argc = $_SERVER['argc'];
        $argv = $_SERVER['argv'];
        if( $argc <= 1 || $argc > 3 ){
            $this->_usageUI();
            exit();
        }
        $command = $argv[1];
        $option = isset( $argv[2] ) ? $argv[2] : null ;
        switch( $command ){
            case 'start':
                // 只有以daemon形式启动服务的时候，将服务注册到redis
                if( '-d' === $option ){
                    $this->httpSetting['daemonize'] = true;
                    $this->tcpSetting['daemonize'] = true;
                    $this->_registerService();
                }
                $this->_run();
                break;
            case 'reload':
                $idJson = file_get_contents( $this->httpSetting['pidFile'] );
                $idArray = json_decode( $idJson, true );
                file_put_contents( $this->httpSetting['workerPidFile'], '' );
                file_put_contents( $this->httpSetting['taskerPidFile'], '' );
                posix_kill( $idArray['managerPid'], SIGUSR1 );
                break;
            case 'status':
                $this->_statusUI();
                if( is_file( $this->httpSetting['workerPidFile'] ) && is_file( $this->httpSetting['taskerPidFile'] ) ){
                    //读取所有进程，并列出来
                    $idsJson = file_get_contents( $this->httpSetting['pidFile'] );
                    $idsArr = json_decode( $idsJson, true );
                    $workerPidString = rtrim( file_get_contents( $this->httpSetting['workerPidFile'] ), '|' );
                    $taskerPidString = rtrim( file_get_contents( $this->httpSetting['taskerPidFile'] ), '|' );
                    $workerPidArr = explode( '|', $workerPidString );
                    $taskerPidArr = explode( '|', $taskerPidString );
                    foreach( $workerPidArr as $workerPidItem ){
                        $tempIdPid = explode( ':', $workerPidItem );
                        echo str_pad( $idsArr['masterPid'], 22, ' ', STR_PAD_BOTH ),
                        str_pad( $idsArr['managerPid'], 14, ' ', STR_PAD_BOTH ),
                        str_pad( $tempIdPid[0], 5, ' ', STR_PAD_BOTH ),
                        str_pad( $tempIdPid[1], 12, ' ', STR_PAD_BOTH );
                        echo PHP_EOL;
                    }
                    foreach( $taskerPidArr as $taskerPidItem ){
                        $tempIdPid = explode( ':', $taskerPidItem );
                        echo str_pad( $idsArr['masterPid'], 22, ' ', STR_PAD_BOTH ),
                        str_pad( $idsArr['managerPid'], 14, ' ', STR_PAD_BOTH ),
                        str_pad( $tempIdPid[0], 5, ' ', STR_PAD_BOTH ),
                        str_pad( $tempIdPid[1], 12, ' ', STR_PAD_BOTH );
                        echo PHP_EOL;
                    }
                }
                break;
            case 'stop':
                // 删除redis中服务注册的信息
                \Swoole\Runtime::enableCoroutine($flags = SWOOLE_HOOK_ALL);
                go(function () {
                    $redis = new \Redis();
                    $redis->connect( $this->serviceRegisterSetting['host'], $this->serviceRegisterSetting['port'] );
                    $redis->hdel( $this->serviceRegisterSetting['serviceName'], $this->getLocalIp() );
                });
                // 获取pid们
                $idJson = file_get_contents( $this->httpSetting['pidFile'] );
                $idArray = json_decode( $idJson, true );
                @unlink( $this->httpSetting['pidFile'] );
                @unlink( $this->httpSetting['workerPidFile'] );
                @unlink( $this->httpSetting['taskerPidFile'] );
                posix_kill( $idArray['masterPid'], SIGTERM );
                break;
            default:
                $this->_usageUI();
                break;
        }
    }
    /*
     * @desc : 开启服务
     */
    private function _run(){
        $this->_statusUI();
        $pool = new \Swoole\Process\Pool(2,SWOOLE_IPC_UNIXSOCK );
        //让每个OnWorkerStart回调都自动创建一个协程
        $pool->set(['enable_coroutine' => true]);
        $pool->on('workerStart', function ($pool, $workerId) {
            // 开启tcp服务器
            $this->tcpServer = new \Swoole\Coroutine\Server($this->tcpSetting['host'], $this->tcpSetting['port'] , false, true);
            echo "Worker#{$workerId} is started\n";
            \Swoole\Coroutine::set($this->coroutineSetting);
            $this->tcpServer->set($this->tcpSetting);
            //收到15信号关闭服务
            \Swoole\Process::signal(SIGTERM, function () {
                $this->httpServer->shutdown();
                $this->tcpServer->shutdown();
            });
            //接收到新的连接请求 并自动创建一个协程
            $this->tcpServer->handle(function (\Swoole\Coroutine\Server\Connection $conn) {
                while (true) {
                    //接收数据
                    $data = $conn->recv();
                    if (empty($data)) {
                        $conn->close();
                        break;
                    }
                    //发送数据
                    $this->receive($conn,$data);
                }
            });
            //开始监听端口
            $this->tcpServer->start();
        });
        $pool->start();
    }


    /*
     * @desc : 当tcp服务器收到数据包后会回调这里
    */
    public function receive( $conn, $data ){
        //解析客户端发来的数据
        $data = Packet::decode( $data );
        if( null === $data || false === $data ){
            $conn->send( Packet::encode( array(
                'code' => -1,
                'message' => 'Wrong Data Format',
            ) ) );
            return true;
        }
        /*
        SW : 单个请求,等待结果
        SN : 单个请求,不等待结果
        MW : 多个请求,等待结果
        MN : 多个请求,不等待结果
        */
        $send_data = [];
        switch( $data['type'] ){
            // 单个请求,等待结果
            case 'SW':
                $send_data = $this->process( $conn, $data );
                $this->processSend($conn,$send_data);
                break;
            case 'SN':
                $this->process( $conn, $data );
                $this->processSend($conn,[
                    'code' => 0,
                    'message' => '任务投递成功',
                ]);
                break;
            // 多个请求,等待结果
            case 'MW':
                //$key是客户端自定义的数据name，$item则是具体需要映射的数据
                foreach( $data['param'] as $key => $item ){
                    $taskData = array(
                        'requestId' => $data['requestId'],
                        'type' => $data['type'],
                        'name' => $key,
                        'param' => $item,
                    );
                    $send_data[] = $this->process( $conn, $taskData );
                }
                $this->processSend($conn,$send_data);
                break;
            case 'MN':
                foreach( $data['param'] as $key=>$item ){
                    $taskData=array(
                        'requestId' => $data['requestId'],
                        'type' => $data['type'],
                        'param' => $item,
                    );
                    $send_data[] = $this->process( $conn, $taskData );
                }
                $this->processSend($conn,$send_data);
                break;
            default:
                $this->processSend($conn,[
                    'code' => -1,
                    'message' => 'Wrong Request Type',
                ]);
                break;
        }
        return;
    }

    /*
     * 返回数据
     */
    private function processSend( $conn,  $taskData ){
        $rs = $conn->send(Packet::encode( $taskData ));
    }

    // 具体业务逻辑
    abstract public function process( $conn, $param );

    /*
     * @desc : swoole http服务器收到的请求会打到这里来
     * @param : model 模型模型
     * @param : method 方法名称
     * @param : param 参数列表
     * @param : requestId
     * @param : type
     */
    public function request( $request, $response ){

        $fd = $request->fd;
        //解析客户端发来的数据
        $rawContent = trim( $request->rawContent() );
        $rawContentArr = Packet::decode( $request->rawContent(), 'http' );
        if( false === $rawContentArr ){
            $response->end( Packet::encode( array(
                'code' => -1,
                'message' => 'Wrong Data Format',
            ), 'http' ) );
            return;
        }
        // 组装数据包
        $data = $rawContentArr;
        $data['fd'] = $fd;
        $data['swoole']['header'] = $request->header;
        $data['swoole']['server'] = $request->server;
        $data['rawContent'] = $rawContent;

        /*
        SW : 单个请求,等待结果
        SN : 单个请求,不等待结果
        MW : 多个请求,等待结果
        MN : 多个请求,不等待结果
        */
        switch( $data['type'] ){
            // 单个请求,等待结果
            case 'SW':
                $taskId = $this->httpServer->task( $data, -1, function ( $server, $taskId, $resultData ) use ( $response ) {
                    $this->onHttpFinished( $server, $taskId, $resultData, $response );
                } );
                self::$taskData[$data['requestId']]['taskKey'][$taskId] = 'single';
                break;
            case 'SN':
                $this->httpServer->task( $data );
                $response->end( Packet::encode( array(
                    'code' => 0,
                    'message' => '任务投递成功',
                ), 'http' ) );
                break;
            // 多个请求,等待结果
            case 'MW':
                //$key是客户端自定义的数据name，$item则是具体需要映射的数据
                foreach( $data['param'] as $key => $item ){
                    $taskData = array(
                        'requestId' => $data['requestId'],
                        'fd' => $fd,
                        'type' => $data['type'],
                        'name' => $key,
                        'param' => $item,
                    );
                    $taskId = $this->httpServer->task( $taskData, -1, function ( $server, $taskId, $resultData ) use ( $response ) {
                        $this->onHttpFinished( $server, $taskId, $resultData, $response );
                    } );
                    self::$taskData[$data['requestId']]['taskKey'][$taskId] = $key;
                }
                break;
            case 'MN':
                foreach( $data['param'] as $key=>$item ){
                    $taskData = array(
                        'requestId' => $data['requestId'],
                        'fd' => $fd,
                        'type' => $data['type'],
                        'param' => $item,
                    );
                    $taskId = $this->httpServer->task( $taskData );
                }
                $response->end( Packet::encode( array(
                    'code' => 0,
                    'message' => '任务投递成功',
                ), 'http' ) );
                break;
            default:
                $response->end( Packet::encode( array(
                    'code' => -1,
                    'message' => 'Wrong Request Type',
                ) ), 'http' );
                break;
        }
        //将fd作为data传给task进程
        return;
    }

    /*
    * @desc : 获取IP地址
    */
    protected function getLocalIp(){
        if( '0.0.0.0' == $this->httpSetting['host'] || '127.0.0.1' == $this->httpSetting['host'] )
        {
            $localIps = swoole_get_local_ip();
            $pattern = array(
                '10\.',
                '172\.1[6-9]\.',
                '172\.2[0-9]\.',
                '172\.31\.',
                '192\.168\.'
            );
            foreach( $localIps as $ipItem ){
                if(preg_match('#^' . implode('|', $pattern) . '#', $ipItem)) {
                    return $ipItem;
                }
            }
        }
        return $this->httpSetting['host'];
    }

    /*
    * @desc : 将服务注册到redis中
    */
    private function _registerService(){
        if(isset($this->tcpSetting['daemonize']) && $this->tcpSetting['daemonize'] === true ){
            if( isset( $this->serviceRegisterSetting['host'] ) && isset( $this->serviceRegisterSetting['port'] ) ){
                \Swoole\Runtime::enableCoroutine($flags = SWOOLE_HOOK_ALL);
                go(function () {
                    $redis = new \Redis();
                    if( false !== $redis->connect( $this->serviceRegisterSetting['host'], $this->serviceRegisterSetting['port'] ) ){
                        // 大概数据类似于：
                        // account-server => [
                        //   192.168.0.111 => 6666:6667 前面是http端口，后面是tcp端口
                        // ]
                        $redis->hmset( $this->serviceRegisterSetting['serviceName'], array(
                            //$this->httpSetting['host'] => $this->httpSetting['port'].':'.$this->tcpSetting['port'],
                            $this->getLocalIp() => $this->httpSetting['port'].':'.$this->tcpSetting['port'],
                        ) );
                    }else{
                        echo " !!! WARNING !!! : Service Register To Redis Fail!".PHP_EOL;
                        //exit('Service Register Fail.'.PHP_EOL);
                    }
                });
            }
        }
    }

    /*
     * @desc : 显示使用方法UI
     */
    private function _usageUI(){
        echo PHP_EOL.PHP_EOL.PHP_EOL;
        echo "--------------------------------------------------------------------------".PHP_EOL;
        echo "|                  -- ------- *     |----  |----   ----                    |".PHP_EOL;
        echo "|                 -    |    |     |    | |    | |                        |".PHP_EOL;
        echo "|                 -     |    |     |----  |----  |                        |".PHP_EOL;
        echo "|                 -    |    |     | \    |      |                        |".PHP_EOL;
        echo "|                      |    |     |   \  |       ----                    |".PHP_EOL;
        echo "--------------------------------------------------------------------------".PHP_EOL;
        echo 'USAGE: php index.php commond'.PHP_EOL;
        echo '1. start,以debug模式开启服务，此时服务不会以daemon形式运行'.PHP_EOL;
        echo '2. start -d,以daemon模式开启服务'.PHP_EOL;
        echo '3. status,查看服务器的状态'.PHP_EOL;
        echo '4. stop,停止服务器'.PHP_EOL;
        echo '5. reload,热加载所有业务代码'.PHP_EOL.PHP_EOL.PHP_EOL.PHP_EOL;
        exit;
    }

    /*
     * @desc : 显示服务状态UI
     */
    private function _statusUI(){
        echo PHP_EOL;
        //打印服务器字幕
        swoole_set_process_name("chj-master-thread");
        echo PHP_EOL.PHP_EOL.PHP_EOL;
        echo "--------------------------------------------------------------------------".PHP_EOL;
        echo "|                   ------- *     |----  |----   ----                    |".PHP_EOL;
        echo "|                      |    |     |    | |    | |                        |".PHP_EOL;
        echo "|                      |    |     |----  |----  |                        |".PHP_EOL;
        echo "|                      |    |     | \    |      |                        |".PHP_EOL;
        echo "|                      |    |     |   \  |       ----                    |".PHP_EOL;
        echo "--------------------------------------------------------------------------".PHP_EOL;
        echo "\033[1A\n\033[K-----------------------\033[47;30m CHJ Server \033[0m-----------------------------\n\033[0m";
        echo "    Version:0.2 Beta, PHP Version:".PHP_VERSION.PHP_EOL;
        echo "         The Server is running on TCP".PHP_EOL.PHP_EOL;
        echo "--------------------------\033[47;30m PORT \033[0m---------------------------\n";
        echo "                     TCP:".$this->tcpSetting['port']."\n\n";
        echo "------------------------\033[47;30m PROCESS \033[0m---------------------------\n";
        echo "      MasterPid---ManagerPid---WorkerId---WorkerPid".PHP_EOL;
    }
}
