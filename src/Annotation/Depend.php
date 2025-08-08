<?php
/**
 * Depend.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Depend extends AbstractAnnotation
{
    public function __construct(
        public ?string $id = null,
        public array $options = [],
        public int $priority = 0
    ) {
    }
}
