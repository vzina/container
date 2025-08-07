<?php
/**
 * ProxyManager.php
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

class ProxyManager
{
    protected array $proxies = [];

    public function __construct(
        protected array $classMap = [],
        protected string $proxyDir = ''
    ) {
        $this->proxies = $this->generateProxyFiles($this->initProxiesByReflectionClassMap($this->classMap));
    }

    public function getProxies(): array
    {
        return $this->proxies;
    }

    public function getProxyDir(): string
    {
        return $this->proxyDir;
    }

    public function getAspectClasses(): array
    {
        $aspectClasses = [];
        $classesAspects = AspectCollector::get('classes', []);
        foreach ($classesAspects as $aspect => $rules) {
            foreach ($rules as $rule) {
                if (isset($this->proxies[$rule])) {
                    $aspectClasses[$aspect][$rule] = $this->proxies[$rule];
                }
            }
        }
        return $aspectClasses;
    }

    protected function generateProxyFiles(array $proxies = []): array
    {
        $proxyFiles = [];
        if (! $proxies) {
            return $proxyFiles;
        }
        if (! file_exists($this->getProxyDir())) {
            mkdir($this->getProxyDir(), 0755, true);
        }

        $gen = new NetteGenerator();
        foreach ($proxies as $className => $sourcePath) {
            $proxyFiles[$className] = $this->putProxyFile($gen, $className, $sourcePath);
        }

        return $proxyFiles;
    }

    protected function putProxyFile(CodeGeneratorInterface $gen, string $className, string $sourcePath): string
    {
        $proxyFilePath = $this->getProxyFilePath($className);
        $modified = true;
        if (file_exists($proxyFilePath)) {
            $modified = $this->isModified($className, $proxyFilePath);
        }

        if ($modified) {
            file_put_contents($proxyFilePath, $gen->genProxyCode($className, $sourcePath));
        }

        return $proxyFilePath;
    }

    protected function isModified(string $className, string $proxyFilePath = null): bool
    {
        $proxyFilePath = $proxyFilePath ?? $this->getProxyFilePath($className);
        $time = filemtime($proxyFilePath);
        $origin = $this->classMap[$className];

        return $time < filemtime($origin);
    }

    protected function getProxyFilePath($className)
    {
        return $this->getProxyDir() . str_replace('\\', '_', $className) . '.proxy.php';
    }

    protected function initProxiesByReflectionClassMap(array $reflectionClassMap = []): array
    {
        // According to the data of AspectCollector to parse all the classes that need proxy.
        $proxies = [];
        if (! $reflectionClassMap) {
            return $proxies;
        }
        $classesAspects = AspectCollector::get('classes', []);
        foreach ($classesAspects as $rules) {
            foreach ($rules as $rule) {
                foreach ($reflectionClassMap as $class => $path) {
                    if (! $this->isMatch($rule, $class)) {
                        continue;
                    }
                    $proxies[$class] = $path;
                }
            }
        }

        foreach ($reflectionClassMap as $className => $path) {
            if (isset($proxies[$className])) {
                continue;
            }

            if ($annotations = $this->retrieveAnnotations($className)) {
                $annotationsAspects = AspectCollector::get('annotations', []);
                foreach ($annotationsAspects as $rules) {
                    foreach ($rules as $rule) {
                        foreach ($annotations as $annotation) {
                            if ($this->isMatch($rule, $annotation)) {
                                $proxies[$className] = $path;
                            }
                        }
                    }
                }
            }
        }

        return $proxies;
    }

    protected function isMatch(string $rule, string $target): bool
    {
        if (strpos($rule, '::') !== false) {
            [$rule,] = explode('::', $rule);
        }
        if (strpos($rule, '*') === false && $rule === $target) {
            return true;
        }
        $preg = str_replace(['*', '\\'], ['.*', '\\\\'], $rule);
        $pattern = "/^{$preg}$/";

        if (preg_match($pattern, $target)) {
            return true;
        }

        return false;
    }

    protected function retrieveAnnotations(string $className): array
    {
        $defined = [];
        if ($annotations = AnnotationCollector::get($className, [])) {
            foreach ($annotations as $ans) {
                foreach ($ans as $name => $annotation) {
                    if (is_object($annotation)) {
                        $defined[] = $name;
                    } else {
                        $defined = array_merge($defined, array_keys($annotation));
                    }
                }
            }

            $defined = array_unique($defined);
        }
        return $defined;
    }
}
