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

    /**
     * 路由规则列表
     * @var RuleItem[]
     */
    protected array $rules = [];

    /**
     * MISS规则列表
     * @var RuleItem[]
     */
    protected array $miss = [];

    /**
     * 完整路由标识
     * @var string|null
     */
    protected ?string $fullName = null;

    /**
     * 别名
     * @var string|null
     */
    protected ?string $alias = null;

    /**
     * 子路由规则
     * @var string|null
     */
    protected ?string $sub = null;

    /**
     * 绑定信息
     * @var mixed
     */
    protected mixed $bind = null;

    /**
     * 是否已解析
     * @var bool
     */
    protected bool $hasParsed = false;

    public function __construct(Route $router, ?RuleGroup $parent = null, string $name = '', mixed $rule = null, bool $lazy = false)
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
