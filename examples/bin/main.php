#!/usr/bin/env php
<?php
declare (strict_types=1);


use OpenEf\Container\ContainerFactory;
use OpenEf\Container\ScanHandler\ScanConfig;

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

error_reporting(E_ALL & ~E_DEPRECATED);
date_default_timezone_set('Asia/Shanghai');
ini_set('memory_limit', '1G'); // 限制运行内存

defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

(static function () {
    /** @var OpenEf\Container\Container $container */
    $container = ContainerFactory::make(function ($c) {
        $c->extend(ScanConfig::class, fn(ScanConfig $sc) => $sc->merge([
            'paths' => [ // 扫描文件目录
                BASE_PATH . '/app',
            ],
        ]));
    });

    $foo = $container->get(\App\Foo::class);
    echo $foo->test(); // hello
    var_dump($foo->getBar()); // object(App\Bar)
})();