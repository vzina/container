<?php
/**
 * AliasDefinition.php
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

class AliasDefinition extends AbstractDefinition
{
    /**
     * @var string
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    protected $concrete;

    public function __construct(string $key, string $concrete, ContainerInterface $container)
    {
        parent::__construct($key, $container);
        $this->concrete = $concrete;
    }

    public function build(array $constructor = [], array $properties = [], array $methods = [])
    {
        return $this->container->get($this->concrete);
    }
}