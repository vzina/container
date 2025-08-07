<?php
/**
 * AspectLoader.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Reflection;

use ReflectionProperty;

class AspectLoader
{
    public static function load(string $className): array
    {
        $reflectionClass = ReflectionManager::reflectClass($className);
        $properties = $reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC);
        $instanceClasses = $instanceAnnotations = [];
        $instancePriority = null;
        foreach ($properties as $property) {
            if ($property->getName() === 'classes') {
                $instanceClasses = ReflectionManager::getPropertyDefaultValue($property);
            } elseif ($property->getName() === 'annotations') {
                $instanceAnnotations = ReflectionManager::getPropertyDefaultValue($property);
            } elseif ($property->getName() === 'priority') {
                $instancePriority = ReflectionManager::getPropertyDefaultValue($property);
            }
        }

        return [$instanceClasses, $instanceAnnotations, $instancePriority];
    }
}
