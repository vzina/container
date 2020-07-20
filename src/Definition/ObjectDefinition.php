<?php
/**
 * ObjectDefinition.php
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
use Psr\Container\ContainerInterface;

class ObjectDefinition extends AbstractDefinition implements ObjectDefinitionInterface
{
    /**
     * @var object
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    protected $concrete;

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
        string $key,
        object $concrete,
        ContainerInterface $container,
        array $properties = [],
        array $methods = []
    ) {
        parent::__construct($key, $container);
        $this->concrete = $concrete;
        $this->defaultProperties = $properties;
        $this->defaultMethods = $methods;
    }

    public function build(array $constructor = [], array $properties = [], array $methods = [])
    {
        $properties = !empty($properties) ? $properties : $this->defaultProperties;
        foreach($properties as $property => $value) {
            $this->concrete->{$property} = $value;
        }

        $methods = !empty($methods) ? $methods : $this->defaultMethods;
        foreach($methods as $method => $value) {
            call_user_func_array(array($this->concrete, $method), $value);
        }

        return $this->concrete;
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