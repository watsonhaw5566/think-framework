<?php

declare(strict_types=1);

namespace think\route;

use think\Route;
use think\route\traits\RuleManager;
use think\route\traits\RuleMatcher;
use think\route\traits\RuleBinder;
use think\route\traits\RuleConfig;

class RuleGroup extends Rule
{
    use RuleManager;
    use RuleMatcher;
    use RuleBinder;
    use RuleConfig;

    protected $rules = [];

    protected $miss;

    protected $fullName;

    protected $alias;

    protected $sub;

    protected $bind;

    protected $hasParsed;

    public function __construct(Route $router, ?RuleGroup $parent = null, string $name = '', $rule = null, bool $lazy = false)
    {
        $this->router = $router;
        $this->parent = $parent;
        $this->rule   = $rule;
        $this->name   = trim($name, '/');

        if ($name && is_string($rule) || is_null($rule)) {
            if ($rule && is_subclass_of($rule, Dispatch::class, false)) {
                $this->dispatcher($rule);
                $this->rule = '';
            } else {
                $this->sub = $rule ?: $this->name;
            }
        }

        $this->setFullName();

        if ($this->parent) {
            $this->domain = $this->parent->getDomain();
            $this->parent->addRuleItem($this);
        }

        if (!$lazy) {
            $this->parseGroupRule($rule);
        }
    }
}
