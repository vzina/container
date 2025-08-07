<?php
/**
 * PropertyManager.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Reflection;

use OpenEf\Container\Collector\MetadataCollector;

class PropertyManager extends MetadataCollector
{
    protected static array $container = [];

    public static function register(string $annotation, callable $callback): void
    {
        static::$container[$annotation][] = $callback;
    }

    public static function isEmpty(): bool
    {
        return empty(static::$container);
    }
}
