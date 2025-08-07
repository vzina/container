<?php

declare(strict_types=1);

namespace OpenEfTest;

use OpenEf\Container\Generator\AspectParser;
use PHPUnit\Framework\TestCase;

class AspectParserTest extends TestCase
{
    public function testIsMatchClassRule()
    {
        // 测试类级匹配
        [$isMatch, $method] = AspectParser::isMatchClassRule('OpenEfTest\TestClass', 'OpenEfTest\TestClass');
        $this->assertTrue($isMatch);
        $this->assertNull($method);

        // 测试带通配符的类匹配
        [$isMatch, $method] = AspectParser::isMatchClassRule('OpenEfTest\TestClass', 'OpenEfTest\*');
        $this->assertTrue($isMatch);

        // 测试方法级匹配
        [$isMatch, $method] = AspectParser::isMatchClassRule(
            'OpenEfTest\TestClass::testMethod',
            'OpenEfTest\TestClass::testMethod'
        );
        $this->assertTrue($isMatch);
        $this->assertEquals('testMethod', $method);

        // 测试不匹配的情况
        [$isMatch, $method] = AspectParser::isMatchClassRule('OpenEfTest\OtherClass', 'OpenEfTest\Test*');
        $this->assertFalse($isMatch);
    }

    public function testIsMatch()
    {
        $this->assertTrue(
            AspectParser::isMatch('OpenEfTest\TestClass', 'testMethod', 'OpenEfTest\TestClass::test*')
        );

        $this->assertFalse(
            AspectParser::isMatch('OpenEfTest\TestClass', 'otherMethod', 'OpenEfTest\TestClass::test*')
        );
    }
}