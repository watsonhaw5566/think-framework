<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2025 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace think\cache\driver;

use BadFunctionCallException;
use DateInterval;
use DateTimeInterface;
use think\cache\Driver;
use think\exception\InvalidCacheException;

/**
 * Wincache缓存驱动.
 */
class Wincache extends Driver
{
    /**
     * 配置参数.
     *
     * @var array
     */
    protected $options = [
        'prefix'      => '',
        'expire'      => 0,
        'tag_prefix'  => 'tag:',
        'serialize'   => [],
        'fail_delete' => false,
    ];

    /**
     * 架构函数.
     *
     * @param array $options 缓存参数
     *
     * @throws BadFunctionCallException
     */
    public function __construct(array $options = [])
    {
        if (!function_exists('wincache_ucache_info')) {
            throw new BadFunctionCallException('not support: WinCache');
        }

        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
    }

    /**
     * 判断缓存.
     *
     * @param string $name 缓存变量名
     */
    public function has($name): bool
    {
        ++$this->readTimes;

        $key = $this->getCacheKey($name);

        return wincache_ucache_exists($key);
    }

    /**
     * 读取缓存.
     *
     * @param string $name    缓存变量名
     * @param mixed  $default 默认值
     */
    public function get($name, $default = null): mixed
    {
        $key = $this->getCacheKey($name);

        try {
            return wincache_ucache_exists($key) ? $this->unserialize(wincache_ucache_get($key)) : $this->getDefaultValue($name, $default);
        } catch (InvalidCacheException $e) {
            return $this->getDefaultValue($name, $default, true);
        }
    }

    /**
     * 写入缓存.
     *
     * @param string                             $name   缓存变量名
     * @param mixed                              $value  存储数据
     * @param DateInterval|DateTimeInterface|int $expire 有效时间（秒）
     */
    public function set($name, $value, $expire = null): bool
    {
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }

        $key    = $this->getCacheKey($name);
        $expire = $this->getExpireTime($expire);
        $value  = $this->serialize($value);

        if (wincache_ucache_set($key, $value, $expire)) {
            return true;
        }

        return false;
    }

    /**
     * 自增缓存（针对数值缓存）.
     *
     * @param string $name 缓存变量名
     * @param int    $step 步长
     *
     * @return false|int
     */
    public function inc($name, $step = 1)
    {
        $key = $this->getCacheKey($name);

        return wincache_ucache_inc($key, $step);
    }

    /**
     * 自减缓存（针对数值缓存）.
     *
     * @param string $name 缓存变量名
     * @param int    $step 步长
     *
     * @return false|int
     */
    public function dec($name, $step = 1)
    {
        $key = $this->getCacheKey($name);

        return wincache_ucache_dec($key, $step);
    }

    /**
     * 删除缓存.
     *
     * @param string $name 缓存变量名
     */
    public function delete($name): bool
    {
        return wincache_ucache_delete($this->getCacheKey($name));
    }

    /**
     * 清除缓存.
     */
    public function clear(): bool
    {
        return wincache_ucache_clear();
    }

    /**
     * 删除缓存标签.
     *
     * @param array $keys 缓存标识列表
     */
    public function clearTag($keys): void
    {
        wincache_ucache_delete($keys);
    }
}
