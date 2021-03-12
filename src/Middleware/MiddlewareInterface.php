<?php
namespace chj\Swoole\Middleware;

interface MiddlewareInterface
{


    public function handle($request,$response,\Closure $next);

    public function bind(string $key,\Closure $middleware);

    public function remove(string $key);

    public function call($request,$response);


}