<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace think\request;

use think\route\Rule;

/**
 * 路由/控制器/操作 Trait
 */
trait InteractsWithRouting
{
    public function setRule(Rule $rule)
    {
        $this->rule = $rule;

        return $this;
    }

    public function rule()
    {
        return $this->rule;
    }

    public function setRoute(array $route)
    {
        $this->route      = array_merge($this->route, $route);
        $this->mergeParam = false;

        return $this;
    }

    public function setLayer(string $layer)
    {
        $this->layer = $layer;

        return $this;
    }

    public function layer(bool $convert = false): string
    {
        $name = $this->layer ?: '';

        return $convert ? strtolower($name) : $name;
    }

    public function setController(string $controller)
    {
        $this->controller = $controller;

        return $this;
    }

    public function controller(bool $convert = false, bool $base = false): string
    {
        $name = $this->controller ?: '';
        if ($base) {
            $name = basename(str_replace('.', '/', $name));
        }

        return $convert ? strtolower($name) : $name;
    }

    public function setAction(string $action)
    {
        $this->action = $action;

        return $this;
    }

    public function action(bool $convert = false): string
    {
        $name = $this->action ?: '';

        return $convert ? strtolower($name) : $name;
    }
}
