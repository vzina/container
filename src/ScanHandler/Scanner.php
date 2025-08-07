<?php
/**
 * Scanner.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\ScanHandler;

use Nette\PhpGenerator\Attribute;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\InterfaceType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\TraitType;
use OpenEf\Container\Annotation\AnnotationInterface;
use OpenEf\Container\Annotation\Aspect;
use OpenEf\Container\Collector\AnnotationCollector;
use OpenEf\Container\Collector\AspectCollector;
use OpenEf\Container\Collector\MetadataCollector;
use OpenEf\Container\Composer;
use OpenEf\Container\Generator\ProxyManager;
use OpenEf\Container\Reflection\AnnotationReader;
use OpenEf\Container\Reflection\AspectLoader;
use OpenEf\Container\Reflection\ReflectionManager;
use OpenEf\Framework\Contract\ConfigInterface;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

class Scanner
{
    protected Filesystem $filesystem;

    protected string $path = BASE_PATH . '/runtime/container/scan.cache';

    public function __construct(protected ScanConfig $config, protected ScanHandlerInterface $handler)
    {
        $this->filesystem = new Filesystem();
    }

    public function scan(array $classMap = []): array
    {
        $paths = $this->config->getPaths();
        $collectors = $this->config->getCollectors();
        if (! $paths) {
            return [];
        }

        $lastCacheModified = file_exists($this->path) ? filemtime($this->path) : 0;
        if ($lastCacheModified > 0 && $this->config->isCacheable()) {
            return $this->deserializeCachedScanData($collectors);
        }

        $scanned = $this->handler->scan();
        if ($scanned->isScanned()) {
            return $this->deserializeCachedScanData($collectors);
        }

        $this->deserializeCachedScanData($collectors);

        $annotationReader = new AnnotationReader();

        $paths = $this->normalizeDir($paths);

        $classes = ReflectionManager::getAllClasses($paths);

        $this->clearRemovedClasses($collectors, $classes);

        $reflectionClassMap = [];
        foreach ($classes as $className => $reflectionClass) {
            $reflectionClassMap[$className] = $reflectionClass->getFileName();
            if (filemtime($reflectionClass->getFileName()) >= $lastCacheModified) {
                /** @var MetadataCollector $collector */
                foreach ($collectors as $collector) {
                    $collector::clear($className);
                }

                $this->collect($annotationReader, $reflectionClass);
            }
        }

        $this->loadAspects($lastCacheModified);

        $data = [];
        /** @var MetadataCollector|string $collector */
        foreach ($collectors as $collector) {
            $data[$collector] = $collector::serialize();
        }

        // Get the class map of Composer loader
        $classMap = array_merge($reflectionClassMap, $classMap);
        $proxyManager = new ProxyManager($classMap, $this->config->getProxyPath());
        $proxies = $proxyManager->getProxies();
        $aspectClasses = $proxyManager->getAspectClasses();

        $this->filesystem->dumpFile($this->path, serialize([$data, $proxies, $aspectClasses]));
        exit;
    }

    public function collect(AnnotationReader $reader, ReflectionClass $reflection): void
    {
        $className = $reflection->getName();
        if ($path = $this->config->getClassMap()[$className] ?? null) {
            if ($reflection->getFileName() !== $path) {
                // When the original class is dynamically replaced, the original class should not be collected.
                return;
            }
        }
        // Parse class annotations
        foreach ($reader->getAttributes($reflection) as $classAnnotation) {
            if ($classAnnotation instanceof AnnotationInterface) {
                $classAnnotation->collectClass($className);
            }
        }
        // Parse properties annotations
        foreach ($reflection->getProperties() as $property) {
            foreach ($reader->getAttributes($property) as $propertyAnnotation) {
                if ($propertyAnnotation instanceof AnnotationInterface) {
                    $propertyAnnotation->collectProperty($className, $property->getName());
                }
            }
        }
        // Parse methods annotations
        foreach ($reflection->getMethods() as $method) {
            foreach ($reader->getAttributes($method) as $methodAnnotation) {
                if ($methodAnnotation instanceof AnnotationInterface) {
                    $methodAnnotation->collectMethod($className, $method->getName());
                }
            }
        }
        // Parse class constants annotations
        foreach ($reflection->getReflectionConstants() as $classConstant) {
            foreach ($reader->getAttributes($classConstant) as $constantAnnotation) {
                if ($constantAnnotation instanceof AnnotationInterface) {
                    $constantAnnotation->collectClassConstant($className, $classConstant->getName());
                }
            }
        }

        unset($reflection);
    }

    protected function normalizeDir(array $paths): array
    {
        $result = [];
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $result[] = $path;
            }
        }

        if ($paths && ! $result) {
            throw new RuntimeException('The scanned directory does not exist');
        }

        return $result;
    }

    protected function deserializeCachedScanData(array $collectors): array
    {
        if (! file_exists($this->path)) {
            return [];
        }

        [$data, $proxies] = unserialize(file_get_contents($this->path));
        foreach ($data as $collector => $deserialized) {
            /** @var MetadataCollector $collector */
            if (in_array($collector, $collectors)) {
                $collector::deserialize($deserialized);
            }
        }

        return $proxies;
    }

    protected function clearRemovedClasses(array $collectors, array $reflections): void
    {
        $path = BASE_PATH . '/runtime/container/classes.cache';
        $classes = array_keys($reflections);

        $data = [];
        if ($this->filesystem->exists($path)) {
            $data = unserialize(file_get_contents($path));
        }

        $this->filesystem->dumpFile($path, serialize($classes));

        $removed = array_diff($data, $classes);

        foreach ($removed as $class) {
            /** @var MetadataCollector $collector */
            foreach ($collectors as $collector) {
                $collector::clear($class);
            }
        }
    }


    /**
     * Load aspects to AspectCollector by configuration files and ConfigProvider.
     */
    protected function loadAspects(int $lastCacheModified): void
    {
        $aspects = array_unique((array)$this->config->getAspects());

        [$removed, $changed] = $this->getChangedAspects($aspects, $lastCacheModified);
        // When the aspect removed from config, it should be removed from AspectCollector.
        foreach ($removed as $aspect) {
            AspectCollector::clear($aspect);
        }

        foreach ($aspects as $key => $value) {
            if (is_numeric($key)) {
                $aspect = $value;
                $priority = null;
            } else {
                $aspect = $key;
                $priority = (int) $value;
            }

            if (! in_array($aspect, $changed)) {
                continue;
            }

            [$instanceClasses, $instanceAnnotations, $instancePriority] = AspectLoader::load($aspect);

            $classes = $instanceClasses ?: [];
            // Annotations
            $annotations = $instanceAnnotations ?: [];
            // Priority
            $priority = $priority ?: ($instancePriority ?? null);
            // Save the metadata to AspectCollector
            AspectCollector::setAround($aspect, $classes, $annotations, $priority);
        }
    }

    protected function getChangedAspects(array $aspects, int $lastCacheModified): array
    {
        $path = BASE_PATH . '/runtime/container/aspects.cache';
        $classes = [];
        foreach ($aspects as $key => $value) {
            if (is_numeric($key)) {
                $classes[] = $value;
            } else {
                $classes[] = $key;
            }
        }

        $data = [];
        if ($this->filesystem->exists($path)) {
            $data = unserialize(file_get_contents($path));
        }

        $this->filesystem->dumpFile($path, serialize($classes));

        $diff = array_diff($data, $classes);
        $changed = array_diff($classes, $data);
        $removed = [];
        foreach ($diff as $item) {
            $annotation = AnnotationCollector::getClassAnnotation($item, Aspect::class);
            if (is_null($annotation)) {
                $removed[] = $item;
            }
        }

        foreach ($classes as $class) {
            $file = Composer::getLoader()->findFile($class);
            if ($file === false) {
                echo sprintf('Skip class %s, because it does not exist in composer class loader.', $class) . PHP_EOL;
                continue;
            }
            if ($lastCacheModified <= filemtime($file)) {
                $changed[] = $class;
            }
        }

        return [
            array_values(array_unique($removed)),
            array_values(array_unique($changed)),
        ];
    }
}
