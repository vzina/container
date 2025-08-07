<?php

declare(strict_types=1);

namespace OpenEfTest;

use OpenEf\Container\Container;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testContainerGetAndSet()
    {
        $container = new Container();

        // 测试设置和获取服务
        $container['test_service'] = function () {
            return new class {
                public string $name = 'test';
            };
        };

        $this->assertTrue($container->has('test_service'));
        $this->assertEquals('test', $container->get('test_service')->name);
    }

    public function testContainerMakeWithParams()
    {
        $container = new Container();

        // 测试通过make方法实例化带参数的类
        $instance = $container->make(TestClass::class, ['name' => 'phpunit']);
        $this->assertInstanceOf(TestClass::class, $instance);
        $this->assertEquals('phpunit', $instance->name);
    }
}

// 测试用例类
class TestClass
{
    public function __construct(public string $name)
    {
    }
}