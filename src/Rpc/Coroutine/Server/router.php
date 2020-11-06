<?php
require_once './tcp.php';
define('HaveGenerator', class_exists("\\Generator", false));
function array_get($data,$key, $default = '')
{
    if (array_key_exists($key,$data))
    {
        return $data[$key];
    }
    return  $default;
}
function exists2($array, $key)
{
    if ($array instanceof ArrayAccess) {
        return $array->offsetExists($key);
    }

    return array_key_exists($key, $array);
}
function forget(&$array, $keys)
{
    $original = &$array;

    $keys = (array) $keys;

    if (count($keys) === 0) {
        return ;
    }

    foreach ($keys as $key) {
        // if the exact key exists in the top-level, remove it
        if (exists2($array, $key)) {
            unset($array[$key]);
            continue;
        }

        $parts = explode('.', $key);

        // clean up before each pass
        $array = &$original;

        while (count($parts) > 1) {
            $part = array_shift($parts);

            if (isset($array[$part]) && is_array($array[$part])) {
                $array = &$array[$part];
            } else {
                continue 2;
            }
        }

        unset($array[array_shift($parts)]);
    }
}
function array_except($array, $keys)
{
    forget($array, $keys);

    return $array;
}



class Router{

    protected static $map = [];
    protected static $methods = [];
    protected static $lastMethodName = '';
    protected static $prefix = '';
    protected static $groupStack = [];
    static $calls = [];   //
    static $names = [];   //

    /**
     * 创建一组方法
     *
     * @param array $attributes
     * @param callable $callback
     *
     * @return void
     */
    public static function group(array $attributes, callable $callback)
    {
        $attributes = self::mergeLastGroupAttributes($attributes);

        if ((!isset($attributes['prefix']) || empty($attributes['prefix'])) && isset(self::$prefix)) {
            $attributes['prefix'] = self::$prefix;
        }

        self::$groupStack[] = $attributes;

        call_user_func($callback, self::class);

        array_pop(self::$groupStack);
    }

    /**
     * 添加方法
     *
     * @param string $name
     * @param string|callable $action
     * @param array $options
     *  是一个关联数组，它里面包含了一些对该服务函数的特殊设置，详情参考hprose-php文档介绍
     *  https://github.com/hprose/hprose-php/wiki/06-Hprose-%E6%9C%8D%E5%8A%A1%E5%99%A8#addfunction-%E6%96%B9%E6%B3%95
     *
     * @return \Zhuqipeng\LaravelHprose\Routing\Router
     */
    public static function add(string $name, $action, array $options = [])
    {
        if (is_string($action)) {
            $action = ['controller' => $action, 'type' => 'method'];
        } elseif (is_callable($action)) {
            $action = ['callable' => $action, 'type' => 'callable'];
        }

        $action = self::mergeLastGroupAttributes($action);

        if (!empty($action['prefix'])) {
            $name = ltrim(rtrim(trim($action['prefix'], '_') . '_' . trim($name, '_'), '_'), '_');
        }
        switch ($action['type']) {
            case 'method':
                list($class, $method) = self::parseController($action['namespace'], $action['controller']);
                self::addMethod($method, $class, $name, $options);
                self::mapRefMethodParameterName($class, $method, $name);
                break;

            case 'callable':
                self::addFunction($action['callable'], $name, $options);
                self::mapRefFuncParameterName($action['callable'], $name);
                break;
        }

        self::appendMethod($name);
        self::setLastMethodName($name);
        return self::class;
    }

    public static function addMethod($method, $scope, $alias = '', array $options = [])
    {
        $func = array($scope, $method);
        if (!is_callable($func)) {
            throw new Exception('Argument func must be callable.');
        }
        if (is_array($alias) && empty($options)) {
            $options = $alias;
            $alias = '';
        }
        if (empty($alias)) {
            if (is_string($func)) {
                $alias = $func;
            }
            elseif (is_array($func)) {
                $alias = $func[1];
            }
            else {
                throw new Exception('Need an alias');
            }
        }
        $name = strtolower($alias);
        if (!array_key_exists($name, self::$calls)) {
            self::$names[] = $alias;
        }
        if (HaveGenerator) {
            if (is_array($func)) {
                $f = new ReflectionMethod($func[0], $func[1]);
            }
            else {
                $f = new ReflectionFunction($func);
            }
        }
        $call = new stdClass();
        $call->method = $func;
        $call->mode = isset($options['mode']) ? $options['mode'] :0;
        $call->simple = isset($options['simple']) ? $options['simple'] : null;
        $call->oneway = isset($options['oneway']) ? $options['oneway'] : false;
        $call->async = isset($options['async']) ? $options['async'] : false;
        $call->passContext = isset($options['passContext']) ? $options['passContext']: null;
        self::$calls[$name] = $call;
        return self::class;
    }

    public static function addFunction($func, $alias = '', array $options = array()) {
        if (!is_callable($func)) {
            throw new Exception('Argument func must be callable.');
        }
        if (is_array($alias) && empty($options)) {
            $options = $alias;
            $alias = '';
        }
        if (empty($alias)) {
            if (is_string($func)) {
                $alias = $func;
            }
            elseif (is_array($func)) {
                $alias = $func[1];
            }
            else {
                throw new Exception('Need an alias');
            }
        }
        $name = strtolower($alias);
        if (!array_key_exists($name, self::$calls)) {
            self::$names[] = $alias;
        }
        if (HaveGenerator) {
            if (is_array($func)) {
                $f = new ReflectionMethod($func[0], $func[1]);
            }
            else {
                $f = new ReflectionFunction($func);
            }
        }
        $call = new stdClass();
        $call->method = $func;
        $call->mode = isset($options['mode']) ? $options['mode'] : 0;
        $call->simple = isset($options['simple']) ? $options['simple'] : null;
        $call->oneway = isset($options['oneway']) ? $options['oneway'] : false;
        $call->async = isset($options['async']) ? $options['async'] : false;
        $call->passContext = isset($options['passContext']) ? $options['passContext']: null;
        self::$calls[$name] = $call;
        return self::class;
    }

    static function getCalls()
    {
        return [self::$groupStack,self::$calls];
    }
    /**
     * 获取所有已添加方法列表
     *
     * @return array
     */
    public static function getMethods()
    {
        return self::$methods;
    }



    /**
     * 合并最后一组属性
     *
     * @param array $attributes
     *
     * @return array
     */
    private static function mergeLastGroupAttributes(array $attributes)
    {
        if (empty(self::$groupStack)) {
            return self::mergeGroup($attributes, []);
        }

        return self::mergeGroup($attributes, end(self::$groupStack));
    }


    /**
     * 追加至已添加方法列表
     *
     * @param string $methodName
     *
     * @return void
     */
    private static function appendMethod(string $methodName)
    {
        self::$methods[] = $methodName;
    }

    private static function setLastMethodName(string $methodName)
    {
        self::$lastMethodName = $methodName;
    }


    /**
     * 关联函数或方法的参数名
     *
     * @param object $class
     * @param string $method
     * @param string $alias
     * @return void
     */
    private function mapRefMethodParameterName($class, string $method, string $alias)
    {
        $ref = new \ReflectionMethod($class, $method);
        self::$map[$alias]['parameterNames'] = array_map(function ($parameter) {
            return $parameter->name;
        }, $ref->getParameters());
    }

    /**
     * 关联函数或方法的参数名
     *
     * @param callable $callback
     * @param string $alias
     * @return void
     */
    private function mapRefFuncParameterName(callable $callback, string $alias)
    {
        $ref = new \ReflectionFunction($callback);
        self::$map[$alias]['parameterNames'] = array_map(function ($parameter) {
            return $parameter->name;
        }, $ref->getParameters());
    }
    /**
     * 合并新加入的组
     *
     * @param array $new
     * @param array $old
     *
     * @return array
     */
    private static function mergeGroup(array $new, array $old)
    {
        $new['namespace'] = self::formatNamespace($new, $old);
        $new['prefix'] = self::formatPrefix($new, $old);
        return array_merge_recursive(array_except($old, ['namespace', 'prefix']), $new);
    }

    /**
     * 格式化命名空间
     *
     * @param array $new
     * @param array $old
     *
     * @return string
     */
    private static function formatNamespace(array $new, array $old)
    {
        if (isset($new['namespace']) && isset($old['namespace'])) {
            return trim($old['namespace'], '\\') . '\\' . trim($new['namespace'], '\\');
        } elseif (isset($new['namespace'])) {
            return trim($new['namespace'], '\\');
        }
        return array_get($old, 'namespace');
    }

    /**
     * 格式化前缀
     *
     * @param array $new
     * @param array $old
     *
     * @return string
     */
    private static function formatPrefix(array $new, array $old)
    {
        if (isset($new['prefix'])) {
            return trim(array_get($old, 'prefix'), '_') . '_' . trim($new['prefix'], '_');
        }

        return array_get($old, 'prefix', '');
    }

    /**
     * 解析控制器
     *
     * @param string|null $namespace
     * @param string $controller
     *
     * @return array
     */
    private static function parseController($namespace, string $controller): array
    {
        $namespace = $namespace ? $namespace : config('hprose.controller');

        list($classAsStr, $method) = explode('@', $controller);

        $class = resolve(join('\\', array_filter([$namespace, $classAsStr])));

        return [$class, $method];
    }
}

\Router::group(['namespace' => 'App\Controllers'], function ($route) {
    $route::add('getUserByName', function ($name,$tes) {
        return 'name: ' . $name;
    });
    $route::add('getUserByName2', function ($name,$tes) {
        return 'name: ' . $name;
    });
});

var_dump(Router::getMethods()).PHP_EOL;
var_dump(Router::getCalls()).PHP_EOL;
