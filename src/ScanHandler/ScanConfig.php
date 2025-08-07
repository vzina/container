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

    public function has(string $key): bool
    {
        return Arr::has($this->items, $key);
    }

    public function set(string $key, $value = null)
    {
        Arr::set($this->items, $key, $value);

        return $this;
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

    public function getProxyPath(): string
    {
        return (string)$this->get('proxy_path', BASE_PATH . '/runtime/container/proxy/');
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
}
