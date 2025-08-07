<?php

namespace OpenEf\Container;

use OpenEf\Container\Annotation\InjectAspect;
use OpenEf\Container\Collector\AnnotationCollector;
use OpenEf\Container\Collector\AspectCollector;
use OpenEf\Container\ScanHandler\ScanConfig;
use OpenEf\Framework\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;

final class ComponentProvider
{
    public function __invoke(ContainerInterface $container)
    {
        $container->extend(ScanConfig::class, fn(ScanConfig $sc) => $sc->merge([
            'paths' => [
                __DIR__,
            ],
            'collectors' => [
                AnnotationCollector::class,
                AspectCollector::class,
            ],
            'class_map' => [],
            'aspects' => [
                InjectAspect::class
            ],
        ]));
    }
}
