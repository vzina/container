<?php
/**
 * BindDefinition.php
 * PHP version 7
 *
 * @category container
 * @author   Weijian.Ye <yeweijian@3k.com>
 * @license  https://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/vzina
 * @date     2020-07-20
 */
declare(strict_types=1);

namespace EyPhp\Container\Definition;


use EyPhp\Container\Contract\ObjectDefinitionInterface;
use EyPhp\Container\Exception\ContainerException;
use Psr\Container\ContainerInterface;

class BindDefinition extends AbstractDefinition implements ObjectDefinitionInterface
{
    /**
     * @var \ReflectionClass
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    protected $concrete;

    /**
     * @var array
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    protected $defaultConstructor = [];

    /**
     * @var array
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    protected $defaultProperties = [];

    /**
     * @var array
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    protected $defaultMethods = [];

    public function __construct(
        $key,
        \ReflectionClass $concrete,
        ContainerInterface $container,
        array $constructor = [],
        array $properties = [],
        array $methods = []
    ) {
        parent::__construct($key, $container);
        $this->concrete = $concrete;
        $this->defaultConstructor = $constructor;
        $this->defaultProperties = $properties;
        $this->defaultMethods = $methods;
    }

    public function build(array $constructor = [], array $properties = [], array $methods = [])
    {
        $reflectionMethod = $this->concrete->getConstructor();

        if (is_null($reflectionMethod)) {
            $parameters = [];
        } elseif (!empty($constructor)) {
            $parameters = $constructor;
        } elseif (!empty($this->defaultConstructor)) {
            $parameters = $this->defaultConstructor;
        } else {
            $parameters = $this->getConstructorArguments($reflectionMethod);
        }

        $object = $this->concrete->newInstanceArgs($parameters);

        $properties = !empty($properties) ? $properties : $this->defaultProperties;
        foreach($properties as $property => $value) {
            $object->{$property} = $value;
        }

        $methods = !empty($methods) ? $methods : $this->defaultMethods;
        foreach($methods as $method => $value) {
            call_user_func_array(array($object, $method), $value);
        }

        return $object;
    }

    /**
     * @param \ReflectionMethod $constructor
     * @throws ContainerException
     * @throws \ReflectionException
     * @return array
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    protected function getConstructorArguments(\ReflectionMethod $constructor)
    {
        $parameters = [];
        foreach ($constructor->getParameters() as $param) {
            if (!$param->isDefaultValueAvailable()) {
                $dependency = $param->getClass();
                if (is_null($dependency)) {
                    throw new ContainerException('Unable to resolve parameter [' . $param->name .'] in ' . $param->getDeclaringClass()->getName());
                }

                $parameters[] = $this->container->get($dependency->name);
                continue;
            }

            $parameters[] = $param->getDefaultValue();
        }

        return $parameters;
    }

    public function getProperties()
    {
        return $this->defaultProperties;
    }

    public function setProperties(array $properties)
    {
        $this->defaultProperties = $properties;

        return $this;
    }

    public function setProperty(string $propertyName, $value)
    {
        $this->defaultProperties[$propertyName] = $value;

        return $this;
    }

    public function callMethod(string $methodName, array $methodArguments = [])
    {
        $this->defaultMethods[$methodName] = $methodArguments;

        return $this;
    }

    public function callMethods(array $methods)
    {
        $this->defaultMethods = $methods;

        return $this;
    }

    public function getCallMethods()
    {
        return $this->defaultMethods;
    }
}