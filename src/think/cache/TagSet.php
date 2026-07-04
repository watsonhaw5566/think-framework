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

namespace think\cache;

use DateInterval;
use DateTimeInterface;

/**
 * 标签集合.
 */
class TagSet
{
    /**
     * 架构函数.
     *
     * @param array  $tag     缓存标签
     * @param Driver $handler 缓存对象
     */
    public function __construct(protected array $tag, protected Driver $handler)
    {
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
        $this->handler->set($name, $value, $expire);

        $this->append($name);

        return true;
    }

    /**
     * 追加缓存标识到标签.
     *
     * @param string $name 缓存变量名
     */
    public function append(string $name): void
    {
        $name = $this->handler->getCacheKey($name);

        foreach ($this->tag as $tag) {
            $key = $this->handler->getTagKey($tag);
            $this->handler->append($key, $name);
        }
    }

    /**
     * 写入缓存.
     *
     * @param iterable                                $values 缓存数据
     * @param null|DateInterval|DateTimeInterface|int $ttl    有效时间 0为永久
     */
    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $val) {
            $result = $this->set($key, $val, $ttl);

            if (false === $result) {
                return false;
            }
        }

        return true;
    }

    /**
     * 如果不存在则写入缓存.
     *
     * @param string $name   缓存变量名
     * @param mixed  $value  存储数据
     * @param int    $expire 有效时间 0为永久
     *
     * @return mixed
     */
    public function remember($name, $value, $expire = null)
    {
        $result = $this->handler->remember($name, $value, $expire);

        $this->append($name);

        return $result;
    }

    /** 清除缓存. */
    public function clear(): bool
    {
        // 指定标签清除
        foreach ($this->tag as $tag) {
            $keys = $this->handler->getTagItems($tag);
            if (!empty($keys)) {
                $this->handler->clearTag($keys);
            }

            $key = $this->handler->getTagKey($tag);
            $this->handler->delete($key);
        }

        return true;
    }
}
