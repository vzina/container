<?php
declare (strict_types=1);

namespace App;

use OpenEf\Container\Annotation\Aspect;
use OpenEf\Container\Annotation\AspectInterface;
use OpenEf\Container\Generator\ProceedingJoinPoint;

#[Aspect]
class FooAspect implements AspectInterface
{
    public array $classes = [
        'App\Foo::test',
        'App\Foo::test*',
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        echo 'before...';
        return $proceedingJoinPoint->process();
    }
}
