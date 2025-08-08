<?php
/**
 * scans.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @contact yeweijian@eyugame.com
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

return [
    'cacheable' => false,
    'paths' => [
        BASE_PATH . '/app',
    ],
    'aspects' => [], // [InjectAspect::class]
    'class_map' => [], // ['className' => 'filepath']
    'collectors' => [], // [AnnotationCollector::class]
    'dependencies' => [], // ['id' => 'className']
];