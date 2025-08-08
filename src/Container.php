<?php
/**
 * Container.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container;

use Closure;
use OpenEf\Container\Reflection\ReflectionManager;
use Psr\Container\ContainerInterface;

class Container extends \Pimple\Container implements ContainerInterface
{
    public function make(string $className, array $params = []): mixed
    {
        return $this->build($className, $params)($this);
    }

    public function build(string $className, array $params = []): Closure
    {
        return static function (Container $container) use ($className, $params) {
            $refClass = ReflectionManager::reflectClass($className);
            $refConst = $refClass->getConstructor();
            $parameters = (array)$refConst?->getParameters();
            foreach ($parameters as $param) {
                /** @var \ReflectionNamedType $type */
                $type = $param->getType();
                if ($type && $container->has($tName = $type->getName())) {
                    $params[$param->getName()] = $container[$tName];
                }
            }

            return $refClass->newInstanceArgs($params);
        };
    }

    public function componentRegister($provider): static
    {
        $provider($this);

        return $this;
    }

    public function get(string $id)
    {
        if (! $this->offsetExists($id) && class_exists($id)) {
            $this->offsetSet($id, $this->build($id));
        }

        return $this->offsetGet($id);
    }

    public function has(string $id): bool
    {
        return $this->offsetExists($id);
    }
}
