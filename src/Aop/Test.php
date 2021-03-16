<?php
namespace chj\Swoole\Aop;
class Test
{
    /**
     * Method 1
     *
     * @param string $message
     */
    public function method1($message)
    {
        echo "\n", __METHOD__, ":\n", $message, "\n";
    }
    /**
     * Method 2
     *
     * @return int
     */
    public function method2()
    {
        echo "\n", __METHOD__, ":\n";
        return rand(1, 10);
    }
    /**
     * Method 3
     *
     * @throws \Exception
     */
    public function method3()
    {
        echo "\n", __METHOD__, ":\n";
        throw new \Exception('Test Exception.');
    }
}