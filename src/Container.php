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


use Closure;
use Exception;
use EyPhp\Container\Contract\ServiceProviderInterface;
use EyPhp\Container\Definition\AliasDefinition;
use EyPhp\Container\Definition\BindDefinition;
use EyPhp\Container\Definition\ClosureDefinition;
use EyPhp\Container\Definition\ObjectDefinition;
use EyPhp\Container\Definition\ValueDefinition;
use EyPhp\Container\Exception\ContainerException;
use Psr\Container\ContainerInterface;
use ReflectionClass;

class Container implements ContainerInterface
{
    const VERSION = '1.0.0';

    /**
     * 服务定义存储
     * @var array
     */
    protected $definitionsMap = [];

    /**
     * 单例存储
     * @var array
     */
    protected $registrySingletons = [];

    /**
     * 单例绑定key
     * @var array
     */
    protected $singletons = [];

    /**
     * Container constructor.
     */
    public function __construct()
    {
        $this->initialize();
    }

    /**
     * 初始化方法
     * @return void
     */
    protected function initialize()
    {
        $this->registrySingletons = [];
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
    public function set($key, $concrete, array $construct = [], array $properties = [], array $methods = [])
    {
        if ($concrete instanceof Closure) {
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
     * @throws Exception
     * @return BindDefinition
     */
    public function bind($key, $concrete, array $construct = [], array $properties = [], array $methods = [])
    {
        try {
            $reflected = new ReflectionClass($concrete);
            if (!$reflected->isInstantiable()) {
                throw new ContainerException($reflected->getName() . ' Is Not Instantiable.');
            }
            $concrete = $reflected;
        } catch (Exception $e) {
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
     * @throws Exception
     * @return BindDefinition
     */
    public function bindSingleton($key, $concrete, array $construct = [], array $properties = [], array $methods = [])
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
    public function singleton($key, $concrete, array $construct = [], array $properties = [], array $methods = [])
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
     * @throws Exception
     * @return  mixed Entry.
     */
    public function get($key, array $construct = [], array $properties = [], array $methods = [])
    {
        if (isset($this->registrySingletons[$key])) {
            return $this->registrySingletons[$key];
        }

        if (!$this->has($key)) {
            // 将组装一个新的反射定义，如果存在类，将尝试解析所有依赖关系，并在可能的情况下实例化该对象。
            $this->definitionsMap[$key] = $this->bind($key, $key, $construct, $properties, $methods);
        }

        $returnValue = $this->definitionsMap[$key]->build($construct, $properties, $methods);

        if (isset($this->singletons[$key])) {
            return $this->registrySingletons[$key] = $returnValue;
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
            unset($this->definitionsMap[$key], $this->registrySingletons[$key], $this->singletons[$key]);
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
     *
     * @param ServiceProviderInterface $provider
     * @param array                    $values
     * @return $this
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    public function register(ServiceProviderInterface $provider, array $values = [])
    {
        $provider->register($this);

        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }
}