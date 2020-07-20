<?php
/**
 * AbstractDefinition.php
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

abstract class AbstractDefinition
{
    /**
     * @var string
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    protected $key;

    /**
     * @var ContainerInterface
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    protected $container;

    public function __construct(string $key, ContainerInterface $container)
    {
        $this->container = $container;
        $this->key = $key;
    }

    abstract public function build(array $constructor = [], array $properties = [], array $methods = []);
}