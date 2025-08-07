<?php

declare(strict_types=1);

namespace OpenEfTest;

use OpenEf\Container\Collector\AspectCollector;
use OpenEf\Container\Generator\NetteGenerator;
use PHPUnit\Framework\TestCase;

class ProxyGeneratorTest extends TestCase
{
    public function testGenProxyCode()
    {
        // 准备测试类代码
        $sourceCode = <<<'CODE'
<?php
namespace OpenEfTest;

class ProxyTestClass
{
    public function testMethod()
    {
        return 'original';
    }
}
CODE;

        // 写入临时文件
        // 使用实际临时文件路径（而非SplTempFileObject）
        $tempPath = sys_get_temp_dir() . '/proxy_test_class.php';
        file_put_contents($tempPath, $sourceCode);

        // 注册具体方法规则
        AspectCollector::setAround('OpenEfTest\ProxyAspect', ['OpenEfTest\ProxyTestClass'], []);

        // 生成代理代码
        $generator = new NetteGenerator();
        $proxyCode = $generator->genProxyCode(ProxyTestClass::class, $tempPath);

        // 验证代理类是否注入了trait和重写方法
        $this->assertStringContainsString('use \OpenEf\Container\Generator\ProxyTrait;', $proxyCode);
        $this->assertStringContainsString('use \OpenEf\Container\Generator\PropertyTrait;', $proxyCode);
        $this->assertStringContainsString('return self::__proxyCall(', $proxyCode);
    }
}

// 测试用代理类
class ProxyTestClass
{
    public function testMethod()
    {
        return 'original';
    }
}
