<?php

declare(strict_types=1);

namespace think\route\traits;

use Closure;
use think\Request;
use think\Response;
use think\route\RuleItem;

trait RouteRegister
{
    abstract public function rule(string $rule, $route = null, string $method = '*'): RuleItem;

    public function any(string $rule, $route): RuleItem
    {
        return $this->rule($rule, $route, '*');
    }

    public function get(string $rule, $route): RuleItem
    {
        return $this->rule($rule, $route, 'GET');
    }

    public function post(string $rule, $route): RuleItem
    {
        return $this->rule($rule, $route, 'POST');
    }

    public function put(string $rule, $route): RuleItem
    {
        return $this->rule($rule, $route, 'PUT');
    }

    public function delete(string $rule, $route): RuleItem
    {
        return $this->rule($rule, $route, 'DELETE');
    }

    public function patch(string $rule, $route): RuleItem
    {
        return $this->rule($rule, $route, 'PATCH');
    }

    public function head(string $rule, $route): RuleItem
    {
        return $this->rule($rule, $route, 'HEAD');
    }

    public function options(string $rule, $route): RuleItem
    {
        return $this->rule($rule, $route, 'OPTIONS');
    }

    public function view(string $rule, string $template = '', array $vars = []): RuleItem
    {
        return $this->rule($rule, function () use ($vars, $template) {
            return Response::create($template, 'view')->assign($vars);
        }, 'GET');
    }

    public function redirect(string $rule, string $route = '', int $status = 301): RuleItem
    {
        return $this->rule($rule, function (Request $request) use ($status, $route) {
            $search  = $replace = [];
            $matches = $request->rule()->getVars();

            foreach ($matches as $key => $value) {
                $search[]  = '<' . $key . '>';
                $replace[] = $value;
                $search[]  = '{' . $key . '}';
                $replace[] = $value;
                $search[]  = ':' . $key;
                $replace[] = $value;
            }

            $route = str_replace($search, $replace, $route);

            return Response::create($route, 'redirect')->code($status);
        }, '*');
    }

    public function miss(Closure|string $route, string $method = '*'): RuleItem
    {
        return $this->group->miss($route, $method);
    }
}
