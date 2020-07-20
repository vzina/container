<?php
/**
 * Container.php
 * PHP version 7
 *
 * @category container
 * @package  EyPhp\Container
 * @author   Weijian.Ye <yeweijian@3k.com>
 * @license  https://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/vzina
 */
declare(strict_types=1);

namespace EyPhp\Container;


use EyPhp\Container\Contract\ServiceProviderInterface;
use EyPhp\Container\Definition\AliasDefinition;
use EyPhp\Container\Definition\BindDefinition;
use EyPhp\Container\Definition\ClosureDefinition;
use EyPhp\Container\Definition\ObjectDefinition;
use EyPhp\Container\Definition\ValueDefinition;
use EyPhp\Container\Exception\ContainerException;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    const VERSION = '1.0.0';

    /**
     * @var array Definitions map.
     */
    protected $definitionsMap;

    /**
     * @var array Singleton services instances.
     */
    protected $registry;

    /**
     * @var array Singleton services map.
     */
    protected $singletons;

    /**
     * @var array Service keys being built.
     */
    protected $buildingKeys = [];

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Initializes the Container.
     *
     * @return void
     */
    protected function initialize()
    {
        $this->registry = [];
        $this->singletons = [];
        $this->definitionsMap = [];
    }

    /**
     * 检查服务是否存在
     *
     * @param string $key
     * @return  boolean
     */
    public function has($key)
    {
        return isset($this->definitionsMap[$key]);
    }


    /**
     * 设置服务
     *
     * @param string $key
     * @param mixed  $concrete
     * @param array  $construct
     * @param array  $properties
     * @param array  $methods
     * @return ClosureDefinition|ObjectDefinition|ValueDefinition
     */
    public function set(
        $key,
        $concrete,
        array $construct = [],
        array $properties = [],
        array $methods = []
    )
    {

        if ($concrete instanceof \Closure) {
            $definition = new ClosureDefinition($key, $concrete, $this, $construct);
        } elseif (is_object($concrete)) {
            $definition = new ObjectDefinition($key, $concrete, $this, $properties, $methods);
        } else {
            $definition = new ValueDefinition($key, $concrete, $this);
            $this->singletons[$key] = true;
        }

        $this->definitionsMap[$key] = $definition;

        return $definition;
    }

    /**
     * 注册别名
     *
     * @param string $alias
     * @param string $key
     * @return AliasDefinition
     */
    public function alias($alias, $key)
    {
        $definition = new AliasDefinition($alias, $key, $this);
        $this->definitionsMap[$alias] = $definition;

        return $definition;
    }

    /**
     * 绑定对象
     *
     * @param string $key
     * @param string $concrete FQCN
     * @param array  $construct
     * @param array  $properties
     * @param array  $methods
     * @throws \Exception
     * @return BindDefinition
     */
    public function bind(
        $key,
        $concrete,
        array $construct = [],
        array $properties = [],
        array $methods = []
    )
    {
        try {
            $reflected = new \ReflectionClass($concrete);
            if (!$reflected->isInstantiable()) {
                throw new ContainerException($reflected->getName() . ' Is Not Instantiable.');
            }
            $concrete = $reflected;
        } catch (\Exception $e) {
            throw $e;
        }

        $definition = new BindDefinition($key, $concrete, $this, $construct, $properties, $methods);
        $this->definitionsMap[$key] = $definition;

        return $definition;
    }

    /**
     * 将key绑定到单例
     *
     * @param string $key
     * @param string $concrete FQCN
     * @param array  $construct
     * @param array  $properties
     * @param array  $methods
     * @throws \Exception
     * @return BindDefinition
     */
    public function bindSingleton(
        $key,
        $concrete,
        array $construct = [],
        array $properties = [],
        array $methods = []
    )
    {
        $definition = $this->bind($key, $concrete, $construct, $properties, $methods);
        $this->singletons[$key] = true;

        return $definition;
    }

    /**
     * 将服务注册为容器中的单例实例
     *
     * @param string $key
     * @param mixed  $concrete
     * @param array  $construct
     * @param array  $properties
     * @param array  $methods
     * @return ClosureDefinition|ObjectDefinition|ValueDefinition
     */
    public function singleton(
        $key,
        $concrete,
        array $construct = [],
        array $properties = [],
        array $methods = []
    )
    {
        $definition = $this->set($key, $concrete, $construct, $properties, $methods);
        $this->singletons[$key] = true;

        return $definition;
    }

    /**
     * 获取服务
     *
     * @param string $key
     * @param array  $construct
     * @param array  $properties
     * @param array  $methods
     * @throws \Exception
     * @return  mixed Entry.
     */
    public function get($key, array $construct = [], array $properties = [], array $methods = [])
    {
        if (isset($this->registry[$key])) {
            return $this->registry[$key];
        }

        // 防止重复解析
        if (array_key_exists($key, $this->buildingKeys)) {
            throw new ContainerException("当前[{$key}]已定义");
        }

        $this->buildingKeys[$key] = true;

        if (!$this->has($key)) {
            // 将组装一个新的反射定义，如果存在类，将尝试解析所有依赖关系，并在可能的情况下实例化该对象。
            $this->definitionsMap[$key] = $this->bind($key, $key, $construct, $properties, $methods);
        }

        $returnValue = $this->definitionsMap[$key]->build($construct, $properties, $methods);
        unset($this->buildingKeys[$key]);

        if (isset($this->singletons[$key])) {
            return $this->registry[$key] = $returnValue;
        }

        return $returnValue;
    }

    /**
     * 从容器中删除服务.
     *
     * @param string $key
     * @return  boolean
     */
    public function remove($key)
    {
        if (isset($this->definitionsMap[$key])) {
            unset($this->definitionsMap[$key], $this->registry[$key], $this->singletons[$key]);
            return true;
        }

        return false;
    }

    /**
     * 重置容器设置
     *
     * @return  void
     */
    public function reset()
    {
        $this->initialize();
    }

    /**
     * 检查服务是否注册为单例
     *
     * @param string $key
     * @return  boolean
     */
    protected function isSingleton($key)
    {
        return isset($this->singletons[$key]);
    }

    /**
     * 注册服务提供者
     * @param ServiceProviderInterface $provider
     * @return $this
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    public function register(ServiceProviderInterface $provider)
    {
        $provider->register($this);
        return $this;
    }
}