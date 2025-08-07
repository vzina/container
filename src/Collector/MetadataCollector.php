<?php
/**
 * MetadataCollector.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Collector;

use Illuminate\Support\Arr;

abstract class MetadataCollector implements MetadataCollectorInterface
{
    protected static array $container = [];

    public static function get(string $key, $default = null)
    {
        return Arr::get(static::$container, $key) ?? $default;
    }

    public static function set(string $key, $value): void
    {
        Arr::set(static::$container, $key, $value);
    }

    public static function has(string $key): bool
    {
        return Arr::has(static::$container, $key);
    }

    public static function clear(?string $key = null): void
    {
        if ($key) {
            Arr::forget(static::$container, [$key]);
        } else {
            static::$container = [];
        }
    }

    public static function serialize(): string
    {
        return serialize(static::$container);
    }

    public static function deserialize(string $metadata): bool
    {
        static::$container = unserialize($metadata);
        return true;
    }

    public static function list(): array
    {
        return static::$container;
    }
}
