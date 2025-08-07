<?php
/**
 * AnnotationInterface.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Annotation;

interface AnnotationInterface
{
    public function collectClass(string $className): void;
    public function collectClassConstant(string $className, ?string $target): void;
    public function collectMethod(string $className, ?string $target): void;
    public function collectProperty(string $className, ?string $target): void;
}
