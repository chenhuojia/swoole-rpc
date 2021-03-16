<?php
namespace chj\Swoole\Aop;
require_once './Log.php';
require_once './Test.php';
require_once './Aspect.php';

$test = Aspect::addObject(new Test());
$logger = new Log();

$test->before('method1', array($logger, 'save'), ['Log saved (method1).']);

//$test->after('method2', array($logger, 'save'), 'Log saved (method2).');
//$test->onCatchException('method3', array($logger, 'save'), 'Log saved (method3).');
/* @var $test TestClass */
$test->method1('abc');
/*echo "=======\n";
echo $test->method2(), "\n";
echo "=======\n";
$test->method3();
echo "=======\n";*/
