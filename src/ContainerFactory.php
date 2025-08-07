<?php
/**
 * ClassLoader.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container;

use OpenEf\Container\Annotation\Depend;
use OpenEf\Container\Annotation\Inject;
use OpenEf\Container\Collector\AnnotationCollector;
use OpenEf\Container\Reflection\PropertyManager;
use OpenEf\Container\Reflection\ReflectionManager;
use OpenEf\Container\ScanHandler\PcntlScanHandler;
use OpenEf\Container\ScanHandler\ScanConfig;
use OpenEf\Container\ScanHandler\ScanHandlerInterface;
use OpenEf\Container\ScanHandler\Scanner;
use Psr\Container\ContainerInterface;
use RuntimeException;

class ContainerFactory
{
    protected static ContainerInterface $instance;

    public static function make(callable $callback = null, ?ContainerInterface $container = null): ContainerInterface
    {
        if (! isset(static::$instance)) {
            $container = $container ?: new Container([
                ScanConfig::class => fn() => new ScanConfig(),
            ]);

            if (! $container->has(ScanConfig::class)) {
                throw new RuntimeException('ScanConfig object not set');
            }

            // 自定义初始化回调
            $callback && $callback($container);

            // 加载组件
            static::loadComponentProviders($container);

            // 加载代理类
            static::loadClasses($container);

            // 加载依赖对象
            static::loadDependencies($container);

            // 注册注入对象
            static::registerInject($container);

            static::$instance = $container;
        }

        return static::$instance;
    }

    protected static function loadComponentProviders(ContainerInterface $container): void
    {
        /** @var Container $container */
        $providers = Composer::getMergedExtra('open_ef')['config'] ?? [];

        foreach ($providers as $provider) {
            if (is_string($provider) && class_exists($provider) && method_exists($provider, '__invoke')) {
                $container->componentRegister(new $provider);
            }
        }
    }

    protected static function loadClasses(ContainerInterface $container): void
    {
        $config = $container->get(ScanConfig::class);
        $scanner = new Scanner(
            $config,
            $container->has(ScanHandlerInterface::class) ? $container->get(ScanHandlerInterface::class) : new PcntlScanHandler()
        );

        $composer = Composer::getLoader();
        $config->getClassMap() or $composer->addClassMap($config->getClassMap());
        $composer->addClassMap(
            $scanner->scan($composer->getClassMap())
        );
    }

    protected static function loadDependencies(ContainerInterface $container): void
    {
        /** @var Container $container */
        $config = $container->get(ScanConfig::class);
        foreach ($config->getDependencies() as $id => $dependency) {
            $container[$id] = $container->build($dependency);
        }

        $builds = [];
        $classes = AnnotationCollector::getClassesByAnnotation(Depend::class);
        foreach ($classes as $class => $depend) {
            /** @var Depend $depend */
            $builds[$depend->id ?: $class][$depend->priority] = [$class, $depend->options];
        }

        ksort($builds);
        foreach ($builds as $id => $depends) {
            $container[$id] = $container->build(...array_pop($depends));
        }
    }

    protected static function registerInject(ContainerInterface $container): void
    {
        PropertyManager::register(
            Inject::class,
            static function ($object, $currentClassName, $targetClassName, $property, $annotation) use ($container) {
                try {
                    $reflectionProperty = ReflectionManager::reflectProperty($currentClassName, $property);
                    if ($container->has($annotation->value)) {
                        $reflectionProperty->setValue($object, $container->get($annotation->value));
                    } elseif ($annotation->required) {
                        throw new RuntimeException("No entry or class found for '{$annotation->value}'");
                    }
                } catch (\Throwable $throwable) {
                    if ($annotation->required) {
                        throw $throwable;
                    }
                }
            }
        );
    }
}
