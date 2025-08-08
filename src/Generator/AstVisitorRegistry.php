<?php
/**
 * AstVisitorRegistry.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @contact yeweijian@eyugame.com
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Generator;

use InvalidArgumentException;
use SplPriorityQueue;

class AstVisitorRegistry
{
    protected static ?SplPriorityQueue $queue = null;

    protected static array $values = [];

    public static function __callStatic($name, $arguments)
    {
        $queue = static::getQueue();
        if (method_exists($queue, $name)) {
            return $queue->{$name}(...$arguments);
        }
        throw new InvalidArgumentException('Invalid method for ' . __CLASS__);
    }

    public static function insert($value, $priority = 0)
    {
        static::$values[] = $value;
        return static::getQueue()->insert($value, $priority);
    }

    public static function exists($value): bool
    {
        return in_array($value, static::$values);
    }

    public static function getQueue(): SplPriorityQueue
    {
        if (! static::$queue instanceof SplPriorityQueue) {
            static::$queue = new SplPriorityQueue();
        }
        return static::$queue;
    }
}
