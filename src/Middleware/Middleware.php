<?php
namespace chj\Swoole\Middleware;

class Middleware implements MiddlewareInterface
{

    /**
     * 绑定中间件
     * @var array
     */
    private $stack = [];

    /**
     * 响应数据
     * @var
     */
    public $response;

    public function handle($request,$response,\Closure $next)
    {
        // TODO: Implement handle() method.
        return $next($request,$response);
    }

    public function bind(string $key, $middleware)
    {
        if (!isset($this->stack[$key]))
        {
            $class = new \ReflectionClass($middleware);
            $this->stack[$key] = $class->getMethod('handle');
        }
        return true;
    }

    public function remove(string $key)
    {
        // TODO: Implement remove() method.
        if (isset($this->stack[$key]))
        {
            unset($this->stack[$key]);
            return true;
        }
        return false;
    }

    public function call($request,$response)
    {
        // TODO: Implement call() method.
        $this->response = $response;
        foreach ($this->stack as $key=>$value)
        {
            $this->response = $this->handle($request,$this->response,$value);
        }
        return $this->response;
    }

}