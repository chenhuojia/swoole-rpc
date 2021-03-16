<?php
declare(strict_types=1);
namespace chj\Swoole\Aop;

class Aspect
{

    private $_target = [];
    private $_className = [];
    private $_eventCallbacks = [];

    public function __construct()
    {

    }


    public function setTarget(string $name,object $target)
    {
        if (is_object($target)) {
            $this->_target[$name] = $target;
            $this->_className[$name] = get_class($target);
        }
    }

    public function getTarget(string $name)
    {
        if ($this->_target && isset($this->_target[$name]))
        {
            return $this->_target[$name];
        }
        throw new \Exception('未找到切面目标！');
    }


    /**
     * 注册切点前事件
     * @param string $method
     * @param callable $callback
     * @param array $args
     * @throws \Exception
     */
    public function before(string $method, callable $callback,array $args = [])
    {
        $this->_registerEvent('before', $method, $callback, $args);
    }
    /**
     * 注册切点后事件
     * @param string $method
     * @param callable $callback
     * @param array $args
     * @throws \Exception
     */
    public function after(string $method,callable $callback,array $args = [])
    {
        $this->_registerEvent('after', $method, $callback, $args);
    }

    /**
     * 注册切点事件
     * @param string $method
     * @param callable $callback
     * @param array $args
     * @throws \Exception
     */
    public function onCatchException(string $method,callable $callback,array $args = [])
    {
        $this->_registerEvent('onCatchException', $method, $callback, $args);
    }


    /**
     * 注册切点事件
     * @param string $eventName
     * @param string $methodName
     * @param callable $callback
     * @param array $args
     * @throws \Exception
     */
    private function _registerEvent(string $eventName, string $methodName, callable $callback, array $args =[])
    {
        if (!isset($this->_eventCallbacks[$methodName])) {
            $this->_eventCallbacks[$methodName] = [];
        }
        if (!is_callable([$this->_target, $methodName])) {
            throw new \Exception(get_class($this->_target) . '::' . $methodName . ' is not exists.');
        }
        if (is_callable($callback)) {
            $this->_eventCallbacks[$methodName][$eventName] = [$callback,$args];
        } else {
            $callbackName = Aspect::getCallbackName($callback);
            throw new \Exception($callbackName . ' is not callable.');
        }
    }

    /**
     * Get name of callback
     *
     * @param callback $callback
     * @return string
     */
    public static function getCallbackName($callback)
    {
        $className  = '';
        $methodName = '';
        if (is_array($callback) && 2 == count($callback)) {
            if (is_object($callback[0])) {
                $className = get_class($callback[0]);
            } else {
                $className = (string) $callback[0];
            }
            $methodName = (string) $callback[1];
        } elseif (is_string($callback)) {
             $methodName = $callback;
        }
        return $className . (($className) ? '::' : '') . $methodName;
    }

    /**
     * Trigger event
     *
     * @param string $eventName
     */
    public  function trigger($eventName, $methodName, $target)
    {
       if (isset($this->_eventCallbacks[$methodName][$eventName])) {
           list($callback, $args) = $this->_eventCallbacks[$methodName][$eventName];
           $args[] = $target;
           call_user_func_array($callback, $args);
       }
    }
    /**
     * Execute method
     *
     * @param string $methodName
     * @param array $args
     * @return mixed
     */
/*    public function __call($methodName, $args)
    {
        if (is_callable(array($this->_target, $methodName))) {
            try {
                $this->trigger('before', $methodName, $this->_target);
                $result = call_user_func_array(array($this->_target, $methodName), $args);
                $this->trigger('after', $methodName, $this->_target);
                return $result ? $result : null;
            } catch (\Exception $e) {
                //$this->trigger('onCatchException', $methodName, $e);
                throw $e;
            }
        } else {
            throw new \Exception("Call to undefined method {$this->_className}::$methodName.");
        }
    }*/

}
