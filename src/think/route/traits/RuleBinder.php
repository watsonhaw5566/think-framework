<?php

declare(strict_types=1);

namespace think\route\traits;

use think\helper\Str;
use think\Request;
use think\route\dispatch\Callback as CallbackDispatch;
use think\route\dispatch\Controller as ControllerDispatch;
use think\route\Dispatch;
use think\route\RuleItem;

trait RuleBinder
{
    public function auto(string $bind = '', array|string $middleware = '')
    {
        $this->bind = $bind ?: '/' . $this->getFullName();
        if ($middleware) {
            $this->middleware($middleware);
        }

        return $this;
    }

    public function class(string $class, bool $prefix = true)
    {
        $this->bind = '\\' . $class;
        if ($prefix) {
            $this->prefix('\\' . $class . '@');
        }

        return $this;
    }

    public function controller(string $controller, bool $prefix = true)
    {
        $this->bind = '@' . $controller;
        if ($prefix) {
            $this->prefix($controller . '/');
        }

        return $this;
    }

    public function namespace(string $namespace, bool $prefix = true)
    {
        $this->bind = ':' . $namespace;
        if ($prefix) {
            $this->prefix($namespace . '\\');
        }

        return $this;
    }

    public function module(string $name, bool $prefix = true)
    {
        $this->bind = ':app\\' . $name . '\controller';
        if ($prefix) {
            $this->prefix('app\\' . $name . '\controller\\');
        }

        return $this;
    }

    public function layer(string $layer, bool $prefix = true)
    {
        $this->bind = '/' . $layer;
        if ($prefix) {
            $this->prefix($layer . '/');
        }

        return $this;
    }

    public function getBind()
    {
        return $this->bind ?? '';
    }

    public function checkBind(Request $request, string $url, array $option = [], ?RuleItem $miss = null): Dispatch
    {
        [$bind, $param] = $this->parseBindAppendParam($this->bind);

        [$call, $bind] = match (substr($bind, 0, 1)) {
            '\\'    => ['bindToClass', substr($bind, 1)],
            '@'     => ['bindToController', substr($bind, 1)],
            '/'     => ['bindToLayer', substr($bind, 1)],
            ':'     => ['bindToNamespace', substr($bind, 1)],
            default => ['bindToClass', $bind],
        };

        $name = $this->getFullName();
        $url  = trim(substr(str_replace('|', '/', $url), strlen($name)), '/');

        return $this->{$call}($request, $url, $bind, $param, $option, $miss);
    }

    protected function parseBindAppendParam(string $bind)
    {
        $vars = [];
        if (str_contains($bind, '?')) {
            [$bind, $query] = explode('?', $bind);
            parse_str($query, $vars);
        }

        return [$bind, $vars];
    }

    protected function bindToClass(Request $request, string $url, string $class, array $param = [], array $option = [], ?RuleItem $miss = null): CallbackDispatch
    {
        $array  = explode('/', $url, 2);
        $action = !empty($array[0]) ? $array[0] : $this->config('default_action');

        if (!empty($array[1])) {
            $this->parseUrlParams($array[1], $param);
        }

        return new CallbackDispatch($request, $this, [$class, $action], $param, $option, $miss);
    }

    protected function bindToNamespace(Request $request, string $url, string $namespace, array $param = [], array $option = [], ?RuleItem $miss = null): CallbackDispatch
    {
        $array  = explode('/', $url, 3);
        $class  = !empty($array[0]) ? $array[0] : $this->config('default_controller');
        $method = !empty($array[1]) ? $array[1] : $this->config('default_action');
        $class .= $this->config('controller_suffix') ? 'Controller' : '';

        if (!empty($array[2])) {
            $this->parseUrlParams($array[2], $param);
        }

        return new CallbackDispatch($request, $this, [trim($namespace, '\\') . '\\' . Str::studly($class), $method], $param, $option, $miss);
    }

    protected function bindToController(Request $request, string $url, string $controller, array $param = [], array $option = [], ?RuleItem $miss = null): ControllerDispatch
    {
        $array  = explode('/', $url, 2);
        $action = !empty($array[0]) ? $array[0] : $this->config('default_action');

        if (!empty($array[1])) {
            $this->parseUrlParams($array[1], $param);
        }

        return new ControllerDispatch($request, $this, [$controller, $action], $param, $option, $miss);
    }

    protected function bindToLayer(Request $request, string $url, string $layer, array $param = [], array $option = [], ?RuleItem $miss = null): ControllerDispatch
    {
        $array      = explode('/', $url, 3);
        $controller = !empty($array[0]) ? $array[0] : $this->config('default_controller');
        $action     = !empty($array[1]) ? $array[1] : $this->config('default_action');

        if (!empty($array[2])) {
            $this->parseUrlParams($array[2], $param);
        }

        return new ControllerDispatch($request, $this, [$layer, $controller, $action], $param, $option, $miss);
    }
}
