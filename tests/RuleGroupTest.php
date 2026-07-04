<?php

namespace think\tests;

use PHPUnit\Framework\TestCase;
use think\Route;
use think\route\RuleGroup;

class RuleGroupTest extends TestCase
{
    use InteractsWithApp;

    protected function setUp(): void
    {
        $this->prepareApp();
    }

    public function testRuleManagerAddRule()
    {
        $route = new Route($this->app);
        $group = new RuleGroup($route, null, 'api');

        $ruleItem = $group->addRule('users', 'User/index', 'GET');
        $this->assertInstanceOf(\think\route\RuleItem::class, $ruleItem);

        $rules = $group->getRules();
        $this->assertCount(1, $rules);
    }

    public function testRuleManagerMiss()
    {
        $route = new Route($this->app);
        $group = new RuleGroup($route, null, 'api');

        $group->miss(function () {
            return 'miss-handler';
        });

        $missRule = $group->getMissRule('get');
        $this->assertNotNull($missRule);
    }

    public function testRuleManagerClear()
    {
        $route = new Route($this->app);
        $group = new RuleGroup($route, null, 'api');

        $group->addRule('users', 'User/index');
        $group->addRule('posts', 'Post/index');

        $this->assertCount(2, $group->getRules());

        $group->clear();
        $this->assertCount(0, $group->getRules());
    }

    public function testRuleBinderAuto()
    {
        $route = new Route($this->app);
        $group = new RuleGroup($route, null, 'api');

        $group->auto();

        $this->assertEquals('/api', $group->getBind());
    }

    public function testRuleBinderClass()
    {
        $route = new Route($this->app);
        $group = new RuleGroup($route, null, 'api');

        $group->class('app\\controller\\Api');

        $this->assertEquals('\app\controller\Api', $group->getBind());
    }

    public function testRuleBinderController()
    {
        $route = new Route($this->app);
        $group = new RuleGroup($route, null, 'api');

        $group->controller('Api');

        $this->assertEquals('@Api', $group->getBind());
    }

    public function testRuleBinderNamespace()
    {
        $route = new Route($this->app);
        $group = new RuleGroup($route, null, 'api');

        $group->namespace('app\\api');

        $this->assertEquals(':app\api', $group->getBind());
    }

    public function testRuleBinderModule()
    {
        $route = new Route($this->app);
        $group = new RuleGroup($route, null, 'api');

        $group->module('api');

        $this->assertEquals(':app\api\controller', $group->getBind());
    }

    public function testRuleBinderLayer()
    {
        $route = new Route($this->app);
        $group = new RuleGroup($route, null, 'api');

        $group->layer('service');

        $this->assertEquals('/service', $group->getBind());
    }

    public function testRuleConfigAlias()
    {
        $route = new Route($this->app);
        $group = new RuleGroup($route, null, 'api');

        $group->alias('v1');

        $this->assertEquals('v1', $group->getAlias());
    }

    public function testRuleConfigPrefix()
    {
        $route = new Route($this->app);
        $group = new RuleGroup($route, null, 'api');

        $group->prefix('api/');

        $option = $group->getOption();
        $this->assertEquals('api/', $option['prefix']);
    }

    public function testRuleConfigMergeRuleRegex()
    {
        $route = new Route($this->app);
        $group = new RuleGroup($route, null, 'api');

        $group->mergeRuleRegex(true);

        $option = $group->getOption();
        $this->assertTrue($option['merge_rule_regex']);
    }

    public function testRuleConfigGetFullName()
    {
        $route = new Route($this->app);
        $group = new RuleGroup($route, null, 'api');

        $this->assertEquals('api', $group->getFullName());
    }

    public function testRuleConfigGetDomain()
    {
        $route = new Route($this->app);
        $group = new RuleGroup($route, null, 'api');

        $this->assertEquals('-', $group->getDomain());
    }

    public function testNestedGroups()
    {
        $route = new Route($this->app);

        $route->group('api', function () use ($route) {
            $route->group('v1', function () use ($route) {
                $route->get('users', function () {
                    return 'api-v1-users';
                });
            });
        });

        $request  = $this->createRequest('api/v1/users', 'GET');
        $response = $route->dispatch($request);

        $this->assertEquals('api-v1-users', $response->getContent());
    }

    public function testGroupPattern()
    {
        $route = new Route($this->app);

        $route->group('api', function () use ($route) {
            $route->get('user/<id>', function ($id) {
                return "user-$id";
            });
        })->pattern(['id' => '\d+']);

        $request  = $this->createRequest('api/user/123', 'GET');
        $response = $route->dispatch($request);

        $this->assertEquals('user-123', $response->getContent());
    }

    protected function createRequest($path, $method = 'GET')
    {
        $request = new \think\Request();
        $request->setPathinfo($path);
        $request->method($method);

        return $request;
    }
}
