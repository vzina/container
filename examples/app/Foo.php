<?php
declare (strict_types=1);

namespace App;

use OpenEf\Container\Annotation\Depend;
use OpenEf\Container\Annotation\Inject;

#[Depend]
class Foo
{
    #[Inject]
    private Bar $bar;

    public function test()
    {
        return 'hello' . PHP_EOL;
    }

    public function test2()
    {
        return 'hello2' . PHP_EOL;
    }

    public function getBar(): Bar
    {
        return $this->bar;
    }
}
