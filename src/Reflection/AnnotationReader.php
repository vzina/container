<?php
/**
 * AnnotationReader.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Reflection;

use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionProperty;
use Reflector;

class AnnotationReader
{
    public function __construct()
    {
    }

    public function getClassAnnotations(ReflectionClass $class)
    {
        return $this->getAttributes($class);
    }

    public function getClassAnnotation(ReflectionClass $class, $annotationName)
    {
        $annotations = $this->getClassAnnotations($class);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    public function getPropertyAnnotations(ReflectionProperty $property)
    {
        return $this->getAttributes($property);
    }

    public function getPropertyAnnotation(ReflectionProperty $property, $annotationName)
    {
        $annotations = $this->getPropertyAnnotations($property);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    public function getMethodAnnotations(ReflectionMethod $method)
    {
        return $this->getAttributes($method);
    }

    public function getMethodAnnotation(ReflectionMethod $method, $annotationName)
    {
        $annotations = $this->getMethodAnnotations($method);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    public function getAttributes(Reflector $reflection): array
    {
        $result = [];
        if (! method_exists($reflection, 'getAttributes')) {
            return $result;
        }

        $attributes = $reflection->getAttributes();
        foreach ($attributes as $attribute) {
            if (! class_exists($attribute->getName())) {
                throw new \RuntimeException(sprintf(
                    "No attribute class found for '%s' in %s",
                    $attribute->getName(),
                    match (true) {
                        $reflection instanceof ReflectionClass => $reflection->getName(),
                        $reflection instanceof ReflectionMethod => $reflection->getDeclaringClass()->getName() . sprintf('->%s() method', $reflection->getName()),
                        $reflection instanceof ReflectionProperty => $reflection->getDeclaringClass()->getName() . sprintf('::$%s property', $reflection->getName()),
                        $reflection instanceof ReflectionClassConstant => $reflection->getDeclaringClass()->getName() . sprintf('::%s class constant', $reflection->getName()),
                        default => '',
                    }
                ));
            }

            $result[] = $attribute->newInstance();
        }
        return $result;
    }
}
