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

namespace think;

/**
 * 数据库管理类.
 *
 * @property Config $config
 */
class Db extends DbManager
{
    /**
     * @return Db
     *
     * @codeCoverageIgnore
     */
    public static function __make(Event $event, Config $config, Log $log, Cache $cache)
    {
        $db = new self();
        $db->setConfig($config);
        $db->setEvent($event);
        $db->setLog($log);

        $store = $db->getConfig('cache_store');
        $db->setCache($cache->store($store));
        $db->triggerSql();

        return $db;
    }

    /** 注入模型对象 */
    protected function modelMaker(): void
    {
    }

    /**
     * 设置配置对象
     *
     * @param Config $config 配置对象
     */
    public function setConfig($config): void
    {
        $this->config = $config;
    }

    /**
     * 获取配置参数.
     *
     * @param string $name    配置参数
     * @param mixed  $default 默认值
     *
     * @return mixed
     */
    public function getConfig(string $name = '', $default = null)
    {
        if ('' !== $name) {
            return $this->config->get('database.' . $name, $default);
        }

        return $this->config->get('database', []);
    }

    /** 设置Event对象 */
    public function setEvent(Event $event): void
    {
        $this->event = $event;
    }

    /**
     * 注册回调方法.
     *
     * @param string   $event    事件名
     * @param callable $callback 回调方法
     */
    public function event(string $event, callable $callback): void
    {
        if ($this->event) {
            $this->event->listen('db.' . $event, $callback);
        }
    }

    /**
     * 触发事件.
     *
     * @param string $event  事件名
     * @param mixed  $params 传入参数
     *
     * @return mixed
     */
    public function trigger(string $event, $params = null, bool $once = false)
    {
        if ($this->event) {
            return $this->event->trigger('db.' . $event, $params, $once);
        }
    }
}