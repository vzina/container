<?php
/**
 * MetadataCollectorInterface.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Collector;

interface MetadataCollectorInterface
{

    public static function get(string $key, $default = null);

    public static function set(string $key, $value): void;

    public static function clear(?string $key = null): void;

    public static function serialize(): string;

    public static function deserialize(string $metadata): bool;

    public static function list(): array;
}
