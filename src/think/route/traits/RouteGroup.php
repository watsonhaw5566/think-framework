<?php

declare(strict_types=1);

namespace think\route\traits;

use Closure;
use think\route\RuleGroup;
use think\route\RuleItem;

trait RouteGroup
{
    abstract public function domain(array|string $name, $rule = null);

    public function group(Closure|string $name, $route = null): RuleGroup
    {
        if ($name instanceof Closure) {
            $route = $name;
            $name  = '';
        }

        return (new RuleGroup($this, $this->group, $name, $route, $this->lazy))
            ->removeSlash($this->removeSlash)
            ->mergeRuleRegex($this->mergeRuleRegex);
    }

    public function module(string $name, $route = null, bool|string $bindDomain = false): RuleGroup
    {
        if ($bindDomain) {
            $group = $this->domain(is_string($bindDomain) ? $bindDomain : $name, $route);
        } else {
            $group = $this->group($name, $route);
        }

        return $group->module($name);
    }

    public function pattern(array $pattern)
    {
        $this->group->pattern($pattern);

        return $this;
    }

    public function option(array $option)
    {
        $this->group->option($option);

        return $this;
    }

    public function auto(string $rule = '[:__module__]/[:__controller__]/[:__action__]', $route = ':__module__/:__controller__/:__action__', bool $middleware = false): RuleItem
    {
        return $this->rule($rule, $route)
            ->name('__think_auto_route__')
            ->pattern([
                '__module__'     => '[A-Za-z0-9\.\_]+',
                '__controller__' => '[A-Za-z0-9\.\_]+',
                '__action__'     => '[A-Za-z0-9\_]+',
            ])->default([
                '__module__'     => $this->config['default_module'],
                '__controller__' => $this->config['default_controller'],
                '__action__'     => $this->config['default_action'],
            ])->autoMiddleware($middleware);
    }
}
