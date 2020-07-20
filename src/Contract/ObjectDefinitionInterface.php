<?php
/**
 * ObjectDefinitionInterface.php
 * PHP version 7
 *
 * @category container
 * @author   Weijian.Ye <yeweijian@3k.com>
 * @license  https://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/vzina
 * @date     2020-07-20
 */
declare(strict_types=1);

namespace EyPhp\Container\Contract;


interface ObjectDefinitionInterface
{
    /**
     * 设置属性值
     * @param string $propertyName
     * @param mixed $value
     * @return mixed
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    public function setProperty(string $propertyName, $value);

    /**
     * 批量设置属性值
     * @param array $properties
     * @return mixed
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    public function setProperties(array $properties);

    /**
     * 获取属性列表
     * @return mixed
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    public function getProperties();

    /**
     * 调用属性设置方法
     * @param string $methodName
     * @param array $methodArguments
     * @return mixed
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    public function callMethod(string $methodName, array $methodArguments = array());

    /**
     * 批量调用属性设置方法
     * @param array $methods
     * @return mixed
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    public function callMethods(array $methods);

    /**
     * 获取生成器方法
     * @return mixed
     * @author Weijian.Ye <yeweijian@3k.com>
     */
    public function getCallMethods();
}