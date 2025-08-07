<?php
/**
 * AspectManager.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Reflection;

use OpenEf\Container\Collector\MetadataCollector;

class AspectManager extends MetadataCollector
{
    protected static array $container = [];

    public static function get(string $class, $method = null)
    {
        return static::$container[$class][$method] ?? [];
    }

    public static function has(string $class, $method = null): bool
    {
        return isset(static::$container[$class][$method]);
    }

    public static function set(string $class, $method, $value = null): void
    {
        static::$container[$class][$method] = $value;
    }

    public static function insert($class, $method, $value): void
    {
        static::$container[$class][$method][] = $value;
    }
}
