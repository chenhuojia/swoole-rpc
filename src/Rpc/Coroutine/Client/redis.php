<?php

Co::set(['hook_flags' => SWOOLE_HOOK_ALL | SWOOLE_HOOK_CURL]);

Co\run(function() {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);//此处产生协程调度，cpu切到下一个协程，不会阻塞进程
    $redis->hMSet('method',['test'=>'testss']);
    $redis->hMSet('method',['test2'=>'testss']);
    $redis->hMSet('method',['test3'=>'testss']);
    var_dump($redis->hGetAll('method'));
});