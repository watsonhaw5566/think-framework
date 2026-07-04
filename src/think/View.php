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

use Exception;
use think\contract\TemplateHandlerInterface;
use think\helper\Arr;

/**
 * 视图类.
 */
class View extends Manager
{
    protected $namespace = '\think\view\driver\\';

    /**
     * 模板变量.
     *
     * @var array
     */
    protected $data = [];

    /**
     * 内容过滤.
     *
     * @var mixed
     */
    protected $filter;

    /**
     * 获取模板引擎.
     *
     * @param string $type 模板引擎类型
     *
     * @return TemplateHandlerInterface
     */
    public function engine(?string $type = null)
    {
        return $this->driver($type);
    }

    /**
     * 模板变量赋值
     *
     * @param array|string $name  模板变量
     * @param mixed        $value 变量值
     *
     * @return $this
     */
    public function assign(array|string $name, $value = null)
    {
        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } else {
            $this->data[$name] = $value;
        }

        return $this;
    }

    /**
     * 视图过滤.
     *
     * @param callable $filter 过滤方法或闭包
     *
     * @return $this
     */
    public function filter(?callable $filter = null)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * 解析和获取模板内容 用于输出.
     *
     * @param string $template 模板文件名或者内容
     * @param array  $vars     模板变量
     *
     * @throws Exception
     */
    public function fetch(string $template = '', array $vars = []): string
    {
        return $this->getContent(function () use ($vars, $template) {
            $this->engine()->fetch($template, array_merge($this->data, $vars));
        });
    }

    /**
     * 渲染内容输出.
     *
     * @param string $content 内容
     * @param array  $vars    模板变量
     */
    public function display(string $content, array $vars = []): string
    {
        return $this->getContent(function () use ($vars, $content) {
            $this->engine()->display($content, array_merge($this->data, $vars));
        });
    }

    /**
     * 获取模板引擎渲染内容.
     *
     * @throws Exception
     */
    protected function getContent($callback): string
    {
        // 页面缓存
        ob_start();
        ob_implicit_flush(false);

        // 渲染输出
        try {
            $callback();
        } catch (Exception $e) {
            ob_end_clean();

            throw $e;
        }

        // 获取并清空缓存
        $content = ob_get_clean();

        if ($this->filter) {
            $content = call_user_func_array($this->filter, [$content]);
        }

        return $content;
    }

    /**
     * 模板变量赋值
     *
     * @param string $name  变量名
     * @param mixed  $value 变量值
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * 取得模板显示变量的值
     *
     * @param string $name 模板变量
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->data[$name];
    }

    /**
     * 检测模板变量是否设置.
     *
     * @param string $name 模板变量名
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    protected function resolveConfig(string $name)
    {
        $config = $this->app->config->get('view', []);
        Arr::forget($config, 'type');

        return $config;
    }

    /**
     * 默认驱动.
     *
     * @return null|string
     */
    public function getDefaultDriver()
    {
        return $this->app->config->get('view.type', 'php');
    }
}
