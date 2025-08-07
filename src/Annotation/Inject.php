<?php
/**
 * Inject.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\Annotation;


use Attribute;
use OpenEf\Container\Reflection\PhpDocReaderManager;
use OpenEf\Container\Reflection\ReflectionManager;
use PhpDocReader\AnnotationException as DocReaderAnnotationException;
use Throwable;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Inject extends AbstractAnnotation
{
    public function __construct(public ?string $value = null, public bool $required = true)
    {
    }

    public function collectProperty(string $className, ?string $target): void
    {
        try {
            if (is_null($this->value)) {
                $reflectionClass = ReflectionManager::reflectClass($className);

                $reflectionProperty = $reflectionClass->getProperty($target);

                if (method_exists($reflectionProperty, 'hasType') && $reflectionProperty->hasType()) {
                    /* @phpstan-ignore-next-line */
                    $this->value = $reflectionProperty->getType()?->getName();
                } else {
                    $this->value = PhpDocReaderManager::getInstance()->getPropertyClass($reflectionProperty);
                }
            }

            if (empty($this->value)) {
                throw new AnnotationException("The @Inject value is invalid for {$className}->{$target}");
            }

            parent::collectProperty($className, $target);
        } catch (AnnotationException|DocReaderAnnotationException $exception) {
            if ($this->required) {
                throw new AnnotationException($exception->getMessage());
            }
            $this->value = '';
        } catch (Throwable $exception) {
            throw new AnnotationException("The @Inject value is invalid for {$className}->{$target}. Because {$exception->getMessage()}");
        }
    }
}
