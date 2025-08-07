<?php
/**
 * ProxyTrait.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Generator;

use Closure;
use Illuminate\Pipeline\Pipeline;
use OpenEf\Container\Collector\AnnotationCollector;
use OpenEf\Container\Collector\AspectCollector;
use OpenEf\Container\Reflection\AspectManager;
use OpenEf\Framework\ApplicationContext;

trait ProxyTrait
{
    protected static function __proxyCall(
        string $className,
        string $method,
        array $arguments,
        Closure $closure
    ) {
        $proceedingJoinPoint = new ProceedingJoinPoint($closure, $className, $method, $arguments);
        $result = self::handleAround($proceedingJoinPoint);
        unset($proceedingJoinPoint);
        return $result;
    }

    protected static function __getParamsMap(array $defaultMap, array $args, bool $isVariadic): array
    {
        $map = ['keys' => [], 'order' => []];
        $leftArgCount = count($args);
        foreach ($defaultMap as $key => $value) {
            $arg = $isVariadic ? $args : array_shift($args);
            if (! isset($arg) && $leftArgCount <= 0) {
                $arg = $value instanceof DefaultLiteral ? $value->getRawValue() : $value;
            }
            --$leftArgCount;
            $map['keys'][$key] = $arg;
            $map['order'][] = $key;
        }

        return $map;
    }

    protected static function __getDefaultLiteral(string $className)
    {
        return new DefaultLiteral($className);
    }

    protected static function handleAround(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $className = $proceedingJoinPoint->className;
        $methodName = $proceedingJoinPoint->methodName;

        if (! AspectManager::has($className, $methodName)) {
            AspectManager::set($className, $methodName, []);
            $aspects = array_unique(array_merge(
                static::getClassesAspects($className, $methodName),
                static::getAnnotationAspects($className, $methodName)
            ));

            $queue = new \SplPriorityQueue();
            foreach ($aspects as $aspect) {
                $queue->insert($aspect, AspectCollector::getPriority($aspect));
            }
            while ($queue->valid()) {
                AspectManager::insert($className, $methodName, $queue->current());
                $queue->next();
            }

            unset($annotationAspects, $aspects, $queue);
        }

        if (empty(AspectManager::get($className, $methodName))) {
            return $proceedingJoinPoint->processOriginalMethod();
        }

        return static::makePipeline()->via('process')
            ->through(AspectManager::get($className, $methodName))
            ->send($proceedingJoinPoint)
            ->then(function (ProceedingJoinPoint $proceedingJoinPoint) {
                return $proceedingJoinPoint->processOriginalMethod();
            });
    }

    protected static function makePipeline(): Pipeline
    {
        return new class extends Pipeline {
            protected function carry(): Closure
            {
                return fn($stack, $pipe) => function ($passable) use ($stack, $pipe) {
                    if (is_string($pipe) && class_exists($pipe)) {
                        $pipe = ApplicationContext::getContainer()->get($pipe);
                    }

                    if (! $passable instanceof ProceedingJoinPoint) {
                        throw new \InvalidArgumentException('$passable must is a ProceedingJoinPoint object.');
                    }
                    $passable->pipe = $stack;

                    return method_exists($pipe, $this->method)
                        ? $pipe->{$this->method}($passable)
                        : $pipe($passable);
                };
            }
        };
    }

    protected static function getClassesAspects(string $className, string $method): array
    {
        $aspects = AspectCollector::get('classes', []);
        $matchedAspect = [];
        foreach ($aspects as $aspect => $rules) {
            foreach ($rules as $rule) {
                if (AspectParser::isMatch($className, $method, $rule)) {
                    $matchedAspect[] = $aspect;
                    break;
                }
            }
        }
        // The matched aspects maybe have duplicate aspect, should unique it when use it.
        return $matchedAspect;
    }

    protected static function getAnnotationAspects(string $className, string $method): array
    {
        $matchedAspect = [];

        $classAnnotations = AnnotationCollector::get($className . '._c', []);
        $methodAnnotations = AnnotationCollector::get($className . '._m.' . $method, []);
        $annotations = array_unique(array_merge(array_keys($classAnnotations), array_keys($methodAnnotations)));

        if (! $annotations) {
            return $matchedAspect;
        }

        $aspects = AspectCollector::get('annotations', []);
        foreach ($aspects as $aspect => $rules) {
            foreach ($rules as $rule) {
                foreach ($annotations as $annotation) {
                    if (strpos($rule, '*') !== false) {
                        $preg = str_replace(['*', '\\'], ['.*', '\\\\'], $rule);
                        $pattern = "/^{$preg}$/";
                        if (! preg_match($pattern, $annotation)) {
                            continue;
                        }
                    } elseif ($rule !== $annotation) {
                        continue;
                    }
                    $matchedAspect[] = $aspect;
                }
            }
        }
        // The matched aspects maybe have duplicate aspect, should unique it when use it.
        return $matchedAspect;
    }
}
