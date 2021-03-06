<?php
/*if( 'cli' !== php_sapi_name() ){
    exit( '服务只能运行在cli sapi模式下'.PHP_EOL );
}*/

/*if( !extension_loaded('swoole') ){
    exit( '请安装swoole扩展'.PHP_EOL );
}*/

// 定义系统常量
define( 'DS', DIRECTORY_SEPARATOR );
define( 'ROOT', dirname(__DIR__).DS);
define('HaveGenerator', class_exists("\\Generator", false));
$logConfig = require_once dirname(__FILE__).'/Library/Logger/config.php';
if (! function_exists('array_get')) {
    /**
     * Get the available container instance.
     *
     * @param  string|null  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    function array_get($data,$key, $default = '')
    {
        if (class_exists('\Illuminate\Support\Arr'))
        {
            $value = \Illuminate\Support\Arr::get($data,$key,$default);
            if (blank($value)){
                return $default;
            }
            return  $value;
        }
        if (strpos($key, '.') === false) {
            return $data[$key] ?? $default;
        }
        $segments = array_filter(explode('.', $key));
        $count = count($segments);
        $tmp = $data;
        for ($i=0; $i < $count;$i++)
        {
            if (!isset($tmp[$segments[$i]])) return $default;
            if ($i == ($count -1))
            {
                return $tmp[$segments[$i]];
            }
            $tmp = $tmp[$segments[$i]];
        }
        return  $default;
    }

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


