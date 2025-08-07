<?php
/**
 * ReflectionManager.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Reflection;

use InvalidArgumentException;
use OpenEf\Container\Collector\MetadataCollector;
use OpenEf\Container\Generator\AstParser;
use OpenEf\Container\Generator\NetteGenerator;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Finder\Finder;

class ReflectionManager extends MetadataCollector
{
    protected static array $container = [];

    public static function reflectClass(string $className): ReflectionClass
    {
        if (! isset(static::$container['class'][$className])) {
            if (! class_exists($className) && ! interface_exists($className) && ! trait_exists($className)) {
                throw new InvalidArgumentException("Class {$className} not exist");
            }
            static::$container['class'][$className] = new ReflectionClass($className);
        }
        return static::$container['class'][$className];
    }

    public static function reflectMethod(string $className, string $method): ReflectionMethod
    {
        $key = $className . '::' . $method;
        if (! isset(static::$container['method'][$key])) {
            // TODO check interface_exist
            if (! class_exists($className) && ! trait_exists($className)) {
                throw new InvalidArgumentException("Class {$className} not exist");
            }
            static::$container['method'][$key] = static::reflectClass($className)->getMethod($method);
        }
        return static::$container['method'][$key];
    }

    public static function reflectProperty(string $className, string $property): ReflectionProperty
    {
        $key = $className . '::' . $property;
        if (! isset(static::$container['property'][$key])) {
            if (! class_exists($className)) {
                throw new InvalidArgumentException("Class {$className} not exist");
            }
            static::$container['property'][$key] = static::reflectClass($className)->getProperty($property);
        }
        return static::$container['property'][$key];
    }

    public static function reflectPropertyNames(string $className)
    {
        $key = $className;
        if (! isset(static::$container['property_names'][$key])) {
            if (! class_exists($className) && ! interface_exists($className) && ! trait_exists($className)) {
                throw new InvalidArgumentException("Class {$className} not exist");
            }
            static::$container['property_names'][$key] = value(function () use ($className) {
                $properties = static::reflectClass($className)->getProperties();
                $result = [];
                foreach ($properties as $property) {
                    $result[] = $property->getName();
                }
                return $result;
            });
        }
        return static::$container['property_names'][$key];
    }

    public static function clear(?string $key = null): void
    {
        if ($key === null) {
            static::$container = [];
        }
    }

    public static function getPropertyDefaultValue(ReflectionProperty $property)
    {
        return method_exists($property, 'getDefaultValue')
            ? $property->getDefaultValue()
            : $property->getDeclaringClass()->getDefaultProperties()[$property->getName()] ?? null;
    }

    public static function getAllClasses(array $paths): array
    {
        $finder = new Finder();
        $finder->files()->in($paths)->name('*.php');

        return static::getAllClassesByFinder($finder);
    }

    public static function getAllClassesByFinder(Finder $finder): array
    {
        $parser = new AstParser();
        $reflectionClasses = [];
        foreach ($finder as $file) {
            $stmts = $parser->parse($file->getContents());
            if (! $className = $parser->parseClassByStmts($stmts)) {
                continue;
            }
            $reflectionClasses[$className] = static::reflectClass($className);
        }
        return $reflectionClasses;
    }

    public static function getContainer(): array
    {
        return self::$container;
    }
}
