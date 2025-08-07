<?php
/**
 * AspectParser.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Generator;

use OpenEf\Container\Collector\AnnotationCollector;
use OpenEf\Container\Collector\AspectCollector;

class AspectParser
{
    public static function isMatchClassRule(string $target, string $rule): array
    {
        /*
         * e.g. Foo/Bar
         * e.g. Foo/B*
         * e.g. F*o/Bar
         * e.g. Foo/Bar::method
         * e.g. Foo/Bar::met*
         */
        $ruleMethod = null;
        $ruleClass = $rule;
        $method = null;
        $class = $target;

        if (strpos($rule, '::') !== false) {
            [$ruleClass, $ruleMethod] = explode('::', $rule);
        }
        if (strpos($target, '::') !== false) {
            [$class, $method] = explode('::', $target);
        }

        if ($method == null) {
            if (strpos($ruleClass, '*') === false) {
                /*
                 * Match [rule] Foo/Bar::ruleMethod [target] Foo/Bar [return] true,ruleMethod
                 * Match [rule] Foo/Bar [target] Foo/Bar [return] true,null
                 * Match [rule] FooBar::rule*Method [target] Foo/Bar [return] true,rule*Method
                 */
                if ($ruleClass === $class) {
                    return [true, $ruleMethod];
                }

                return [false, null];
            }

            /**
             * Match [rule] Foo*Bar::ruleMethod [target] Foo/Bar [return] true,ruleMethod
             * Match [rule] Foo*Bar [target] Foo/Bar [return] true,null.
             */
            $preg = str_replace(['*', '\\'], ['.*', '\\\\'], $ruleClass);
            $pattern = "#^{$preg}$#";

            if (preg_match($pattern, $class)) {
                return [true, $ruleMethod];
            }

            return [false, null];
        }

        if (strpos($rule, '*') === false) {
            /*
             * Match [rule] Foo/Bar::ruleMethod [target] Foo/Bar::ruleMethod [return] true,ruleMethod
             * Match [rule] Foo/Bar [target] Foo/Bar::ruleMethod [return] false,null
             */
            if ($ruleClass === $class && ($ruleMethod === null || $ruleMethod === $method)) {
                return [true, $method];
            }

            return [false, null];
        }

        /*
         * Match [rule] Foo*Bar::ruleMethod [target] Foo/Bar::ruleMethod [return] true,ruleMethod
         * Match [rule] FooBar::rule*Method [target] Foo/Bar::ruleMethod [return] true,rule*Method
         */
        if ($ruleMethod) {
            $preg = str_replace(['*', '\\'], ['.*', '\\\\'], $rule);
            $pattern = "#^{$preg}$#";
            if (preg_match($pattern, $target)) {
                return [true, $method];
            }
        } else {
            /**
             * Match [rule] Foo*Bar [target] Foo/Bar::ruleMethod [return] true,null.
             */
            $preg = str_replace(['*', '\\'], ['.*', '\\\\'], $rule);
            $pattern = "#^{$preg}$#";
            if (preg_match($pattern, $class)) {
                return [true, $method];
            }
        }

        return [false, null];
    }

    public static function isMatch(string $class, string $method, string $rule): bool
    {
        [$isMatch,] = self::isMatchClassRule($class . '::' . $method, $rule);

        return $isMatch;
    }

    public static function parse(string $class): RewriteCollection
    {
        $rewriteCollection = new RewriteCollection($class);
        $container = AspectCollector::getContainer();
        foreach ($container as $type => $collection) {
            if ($type === 'classes') {
                static::parseClasses($collection, $class, $rewriteCollection);
            } elseif ($type === 'annotations') {
                static::parseAnnotations($collection, $class, $rewriteCollection);
            }
        }
        return $rewriteCollection;
    }

    private static function parseAnnotations(array $collection, string $class, RewriteCollection $rewriteCollection)
    {
        // Get the annotations of class and method.
        $annotations = AnnotationCollector::get($class);
        $classMapping = $annotations['_c'] ?? [];
        $methodMapping = value(function () use ($annotations) {
            $mapping = [];
            $methodAnnotations = $annotations['_m'] ?? [];
            foreach ($methodAnnotations as $method => $targetAnnotations) {
                $keys = array_keys($targetAnnotations);
                foreach ($keys as $key) {
                    $mapping[$key][] = $method;
                }
            }
            return $mapping;
        });
        $aspects = array_keys($collection);
        foreach ($aspects ?? [] as $aspect) {
            $rules = AspectCollector::getRule($aspect);
            foreach ($rules['annotations'] ?? [] as $rule) {
                // If exist class level annotation, then all methods should rewrite, so return an empty array directly.
                if (isset($classMapping[$rule])) {
                    $rewriteCollection->setLevel(RewriteCollection::CLASS_LEVEL);
                    return;
                }
                if (isset($methodMapping[$rule])) {
                    $rewriteCollection->add($methodMapping[$rule]);
                }
            }
        }
    }

    private static function parseClasses(array $collection, string $class, RewriteCollection $rewriteCollection)
    {
        $aspects = array_keys($collection);
        foreach ($aspects ?? [] as $aspect) {
            $rules = AspectCollector::getRule($aspect);
            foreach ($rules['classes'] ?? [] as $rule) {
                [$isMatch, $method] = static::isMatchClassRule($class, $rule);
                if ($isMatch) {
                    if ($method === null) {
                        $rewriteCollection->setLevel(RewriteCollection::CLASS_LEVEL);
                        return;
                    }
                    $rewriteCollection->add($method);
                }
            }
        }
    }
}
