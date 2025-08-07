<?php
/**
 * DefaultLiteral.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Generator;

class DefaultLiteral
{
    public function __construct(public string $raw)
    {
    }

    public function getRawValue(): mixed
    {
        return new $this->raw;
    }
}
