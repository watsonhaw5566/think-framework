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

use think\route\Domain;
use think\route\Rule;
use think\route\RuleGroup;
use think\route\RuleItem;
use think\route\RuleName;
use think\route\traits\RouteRegister;
use think\route\traits\RouteGroup as RouteGroupTrait;
use think\route\traits\RouteResource;
use think\route\traits\RouteDomain;
use think\route\traits\RouteDispatch;
use think\route\traits\RouteUrl;

class Route
{
    use RouteRegister;
    use RouteGroupTrait;
    use RouteResource;
    use RouteDomain;
    use RouteDispatch;
    use RouteUrl;

    protected $config = [
        'pathinfo_depr'         => '/',
        'url_lazy_route'        => false,
        'url_route_must'        => false,
        'url_case_sensitive'    => false,
        'route_rule_merge'      => false,
        'route_complete_match'  => false,
        'route_auto_group'      => false,
        'remove_slash'          => false,
        'default_route_pattern' => '[\w\.]+',
        'url_html_suffix'       => 'html',
        'controller_layer'      => 'controller',
        'empty_controller'      => 'Error',
        'controller_suffix'     => false,
        'default_module'        => 'index',
        'default_controller'    => 'Index',
        'default_action'        => 'index',
        'action_suffix'         => '',
        'url_common_param'      => true,
        'action_bind_param'     => 'get',
        'api_version'           => 'Api-Version',
    ];

    protected $request;

    protected $ruleName;

    protected $host;

    protected $group;

    protected $domains = [];

    protected $cross;

    protected $lazy = false;

    protected $mergeRuleRegex = false;

    protected $removeSlash = false;

    public function __construct(protected App $app)
    {
        $this->ruleName = new RuleName();
        $this->setDefaultDomain();

        if (is_file($this->app->getRuntimePath() . 'route.php')) {
            $this->import(include $this->app->getRuntimePath() . 'route.php');
        }

        $this->config = array_merge($this->config, $this->app->config->get('route'));

        $this->init();
    }

    protected function init()
    {
        if (!empty($this->config['middleware'])) {
            $this->app->middleware->import($this->config['middleware'], 'route');
        }

        $this->lazy($this->config['url_lazy_route']);
        $this->mergeRuleRegex = $this->config['route_rule_merge'];
        $this->removeSlash    = $this->config['remove_slash'];

        $this->group->removeSlash($this->removeSlash);

        $this->miss(function () {
            return Response::create('', 'html', 204)->header(['Allow' => 'GET, POST, PUT, DELETE']);
        }, 'options');
    }

    public function config(?string $name = null)
    {
        if (is_null($name)) {
            return $this->config;
        }

        return $this->config[$name] ?? null;
    }

    public function lazy(bool $lazy = true)
    {
        $this->lazy = $lazy;

        return $this;
    }

    public function mergeRuleRegex(bool $merge = true)
    {
        $this->mergeRuleRegex = $merge;
        $this->group->mergeRuleRegex($merge);

        return $this;
    }

    protected function setDefaultDomain(): void
    {
        $domain = new Domain($this);

        $this->domains['-'] = $domain;
        $this->group        = $domain;
    }

    public function setGroup(RuleGroup $group): void
    {
        $this->group = $group;
    }

    public function getGroup(?string $name = null)
    {
        return $name ? $this->ruleName->getGroup($name) : $this->group;
    }

    public function getRuleName(): RuleName
    {
        return $this->ruleName;
    }

    public function getName(?string $name = null, ?string $domain = null, string $method = '*'): array
    {
        return $this->ruleName->getName($name, $domain, $method);
    }

    public function import(array $name): void
    {
        $this->ruleName->import($name);
    }

    public function setName(string $name, RuleItem $ruleItem, bool $first = false): void
    {
        $this->ruleName->setName($name, $ruleItem, $first);
    }

    public function setRule(string $rule, ?RuleItem $ruleItem = null): void
    {
        $this->ruleName->setRule($rule, $ruleItem);
    }

    public function getRule(string $rule): array
    {
        return $this->ruleName->getRule($rule);
    }

    public function getRuleList(): array
    {
        return $this->ruleName->getRuleList();
    }

    public function clear(): void
    {
        $this->ruleName->clear();

        if ($this->group) {
            $this->group->clear();
        }
    }

    public function rule(string $rule, $route = null, string $method = '*'): RuleItem
    {
        return $this->group->addRule($rule, $route, $method);
    }

    public function setCrossDomainRule(Rule $rule)
    {
        if (!isset($this->cross)) {
            $this->cross = (new RuleGroup($this))->mergeRuleRegex($this->mergeRuleRegex);
        }

        $this->cross->addRuleItem($rule);

        return $this;
    }

    public function __call($method, $args)
    {
        return call_user_func_array([$this->group, $method], $args);
    }
}
