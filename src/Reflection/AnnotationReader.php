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
                $className = $methodName = $propertyName = $classConstantName = '';
                if ($reflection instanceof ReflectionClass) {
                    $className = $reflection->getName();
                } elseif ($reflection instanceof ReflectionMethod) {
                    $className = $reflection->getDeclaringClass()->getName();
                    $methodName = $reflection->getName();
                } elseif ($reflection instanceof ReflectionProperty) {
                    $className = $reflection->getDeclaringClass()->getName();
                    $propertyName = $reflection->getName();
                } elseif ($reflection instanceof ReflectionClassConstant) {
                    $className = $reflection->getDeclaringClass()->getName();
                    $classConstantName = $reflection->getName();
                }
                $message = sprintf(
                    "No attribute class found for '%s' in %s",
                    $attribute->getName(),
                    $className
                );
                if ($methodName) {
                    $message .= sprintf('->%s() method', $methodName);
                }
                if ($propertyName) {
                    $message .= sprintf('::$%s property', $propertyName);
                }
                if ($classConstantName) {
                    $message .= sprintf('::%s class constant', $classConstantName);
                }
                throw new \RuntimeException($message);
            }
            $result[] = $attribute->newInstance();
        }
        return $result;
    }
}
