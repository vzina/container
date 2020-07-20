<?php
/**
 * ClosureDefinition.php
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


use Psr\Container\ContainerInterface;

class ClosureDefinition extends AbstractDefinition
{
    /**
     * @var \Closure
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    protected $concrete;

    /**
     * @var array
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    protected $defaultConstructor = [];

    public function __construct(string $key, \Closure $concrete, ContainerInterface $container, array $constructor = []) {
        parent::__construct($key, $container);
        $this->concrete = $concrete;
        $this->defaultConstructor = $constructor;
    }

    public function build(array $constructor = [], array $properties = [], array $methods = [])
    {
        $constructor = !empty($constructor) ? $constructor : $this->defaultConstructor;

        return call_user_func_array($this->concrete, $constructor);
    }
}