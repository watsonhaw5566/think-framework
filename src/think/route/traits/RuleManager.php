<?php

declare(strict_types=1);

namespace think\route\traits;

use Closure;
use think\route\Rule;
use think\route\RuleItem;

trait RuleManager
{
    public function addRule(string $rule, $route = null, string $method = '*'): RuleItem
    {
        $name   = is_string($route) ? $route : null;
        $method = strtolower($method);
        if ('' === $rule || '/' === $rule) {
            $rule .= '$';
        }

        $ruleItem = new RuleItem($this->router, $this, $name, $rule, $route, $method);
        $this->addRuleItem($ruleItem);

        return $ruleItem;
    }

    public function addRuleItem(Rule $rule)
    {
        $this->rules[] = $rule;

        return $this;
    }

    public function miss(Closure|string $route, string $method = '*'): RuleItem
    {
        $method              = strtolower($method);
        $ruleItem            = new RuleItem($this->router, $this, null, '', $route, $method);
        $this->miss[$method] = $ruleItem->setMiss();

        return $ruleItem;
    }

    public function getMissRule(string $method = '*'): ?RuleItem
    {
        if (isset($this->miss[$method])) {
            $miss = $this->miss[$method];
        } elseif (isset($this->miss['*'])) {
            $miss = $this->miss['*'];
        }

        return $miss ?? null;
    }

    public function getRules(string $method = ''): array
    {
        if ('' === $method) {
            return $this->rules;
        }

        return array_filter($this->rules, function ($item) use ($method) {
            $ruleMethod = $item->getMethod();

            return '*' == $ruleMethod || str_contains($ruleMethod, $method);
        });
    }

    public function clear(): void
    {
        $this->rules = [];
    }
}
