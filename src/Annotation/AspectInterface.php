<?php
/**
 * AspectInterface.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @contact yeweijian@eyugame.com
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Annotation;

use OpenEf\Container\Generator\ProceedingJoinPoint;

interface AspectInterface
{
    public function process(ProceedingJoinPoint $proceedingJoinPoint);
}
