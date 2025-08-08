<?php
/**
 * ScanConfig.php
 * PHP version 7
 *
 * @package open-ef
 * @author  weijian.ye
 * @link    https://github.com/vzina
 */
declare (strict_types=1);

namespace OpenEf\Container\ScanHandler;

use Illuminate\Support\Arr;

class ScanConfig
{
    public function __construct(protected array $items = [])
    {
    }

    public function get(string $key, $default = null)
    {
        return Arr::get($this->items, $key, $default);
    }

    public function merge($key, array $value = [])
    {
        // 合并配置以项目配置为主
        if (is_array($key)) {
            $this->items = array_merge_recursive($key, $this->items);
        } elseif (is_string($key)) {
            $this->items[$key] = array_merge_recursive($value, (array)$this->get($key));
        }

        return $this;
    }

    public function getRuntimeContainerPath(): string
    {
        return (string)$this->get('runtime_container_path', $this->getAppPath() . '/runtime/container/');
    }

    public function getProxyPath(): string
    {
        return (string)$this->get('proxy_path', $this->getRuntimeContainerPath() . 'proxy/');
    }

    public function isCacheable(): bool
    {
        return (bool)$this->get('cacheable', false);
    }

    public function getClassMap(): array
    {
        return (array)$this->get('class_map');
    }

    public function getPaths(): array
    {
        return (array)$this->get('paths');
    }

    public function getCollectors(): array
    {
        return (array)$this->get('collectors');
    }

    public function getAspects(): array
    {
        return (array)$this->get('aspects');
    }

    public function getDependencies(): array
    {
        return (array)$this->get('dependencies');
    }

    protected function getAppPath(): string
    {
        return defined('BASE_PATH') ? BASE_PATH : sys_get_temp_dir();
    }
}
