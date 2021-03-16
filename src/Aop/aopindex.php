<?php

namespace chj\Swoole\Aop;
require_once './Log.php';
require_once './Test.php';
require_once './Aspect.php';

$aspect = new Aspect();

$aspect->setTarget('log',new Log());
$aspect->setTarget('test',new Test());


