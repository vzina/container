<?php

declare(strict_types=1);

namespace OpenEfTest;

use OpenEf\Container\Generator\RewriteCollection;
use PHPUnit\Framework\TestCase;

class RewriteCollectionTest extends TestCase
{
    public function testShouldRewrite()
    {
        $collection = new RewriteCollection(TestClass::class);

        // 添加具体方法
        $collection->add('testMethod');
        $this->assertTrue($collection->shouldRewrite('testMethod'));
        $this->assertFalse($collection->shouldRewrite('otherMethod'));

        // 添加通配符方法
        $collection->add('*Method');
        $this->assertTrue($collection->shouldRewrite('otherMethod'));

        // 测试类级重写（排除__construct）
        $collection->setLevel(RewriteCollection::CLASS_LEVEL);
        $this->assertTrue($collection->shouldRewrite('testMethod'));
        $this->assertFalse($collection->shouldRewrite('__construct'));
    }
}