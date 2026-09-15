<?php

declare (strict_types=1);

namespace think\tests;

use Mockery as m;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use think\Request;
use think\Route;
use think\route\AttributeRoute;

class AttributeRouteTest extends TestCase
{
    use InteractsWithApp;

    /** @var Route|MockInterface */
    protected Route $route;

    protected function setUp(): void
    {
        $this->prepareApp();

        $this->config->shouldReceive('get')->with('route')->andReturn([
            'url_route_must'  => true,
            'url_html_suffix' => false,
        ]);

        $this->route = new Route($this->app);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    protected function makeRequest(string $path, string $method = 'GET')
    {
        $request = m::mock(Request::class)->makePartial();
        $request->shouldReceive('host')->andReturn('localhost');
        $request->shouldReceive('pathinfo')->andReturn($path);
        $request->shouldReceive('url')->andReturn('/' . $path);
        $request->shouldReceive('method')->andReturn(strtoupper($method));
        $request->shouldReceive('ext')->andReturn('');

        return $request;
    }

    /**
     * 测试带 Resource 前缀的控制器路由注册
     */
    public function testResourcePrefixedRoutes()
    {
        $scanner = new AttributeRoute($this->app, $this->route);
        $scanner->scan([__DIR__ . '/stub/controller']);

        // GET /users => index
        $request  = $this->makeRequest('users', 'GET');
        $response = $this->route->dispatch($request);
        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('index', $response->getContent());

        // GET /users/123 => show
        $request  = $this->makeRequest('users/123', 'GET');
        $response = $this->route->dispatch($request);
        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('show:123', $response->getContent());

        // POST /users => store
        $request  = $this->makeRequest('users', 'POST');
        $response = $this->route->dispatch($request);
        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('store', $response->getContent());

        // PUT /users/456 => update
        $request  = $this->makeRequest('users/456', 'PUT');
        $response = $this->route->dispatch($request);
        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('update:456', $response->getContent());
    }

    /**
     * 测试无前缀控制器的方法级路由
     */
    public function testPlainMethodRoutes()
    {
        $scanner = new AttributeRoute($this->app, $this->route);
        $scanner->scan([__DIR__ . '/stub/controller']);

        // GET /hello => hello
        $request  = $this->makeRequest('hello', 'GET');
        $response = $this->route->dispatch($request);
        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('hello', $response->getContent());

        // POST /world => world
        $request  = $this->makeRequest('world', 'POST');
        $response = $this->route->dispatch($request);
        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('world', $response->getContent());
    }

    /**
     * 测试无注解的方法不会被注册为路由
     */
    public function testNonAnnotatedMethodNotRegistered()
    {
        $scanner = new AttributeRoute($this->app, $this->route);
        $scanner->scan([__DIR__ . '/stub/controller']);

        // internal 方法无 #[Route] 注解，且路径不匹配任何已注册路由，应返回 404
        $this->expectException(\think\exception\RouteNotFoundException::class);

        $request = $this->makeRequest('users/internal/extra', 'GET');
        $this->route->dispatch($request);
    }

    /**
     * 测试 HTTP 方法不匹配时返回 404
     */
    public function testMethodMismatchReturnsNotFound()
    {
        $scanner = new AttributeRoute($this->app, $this->route);
        $scanner->scan([__DIR__ . '/stub/controller']);

        // show 只接受 GET，用 DELETE 访问应失败
        $this->expectException(\think\exception\RouteNotFoundException::class);

        $request = $this->makeRequest('users/123', 'DELETE');
        $this->route->dispatch($request);
    }

    /**
     * 提取路由规则上注册的中间件名称列表
     *
     * @return string[]
     */
    protected function getRuleMiddlewares(string $rule, string $method = 'GET'): array
    {
        $rules  = $this->route->getRule($rule);
        $method = strtolower($method);

        foreach ($rules as $item) {
            if (in_array($item->getMethod(), ['*', $method])) {
                $middlewares = $item->getOption('middleware', []);
                $names       = [];
                foreach ($middlewares as $mw) {
                    if (is_array($mw)) {
                        $names[] = is_array($mw[0]) ? $mw[0][0] : $mw[0];
                    } else {
                        $names[] = $mw;
                    }
                }

                return $names;
            }
        }

        return [];
    }

    /**
     * 测试类级中间件注解应用到所有路由
     */
    public function testClassLevelMiddlewareAppliedToAllRoutes()
    {
        $scanner = new AttributeRoute($this->app, $this->route);
        $scanner->scan([__DIR__ . '/stub/controller']);

        // dashboard 路由应有类级中间件 classAuth
        $this->assertContains('classAuth', $this->getRuleMiddlewares('admin/dashboard'));

        // publicAction 路由也应有类级中间件 classAuth
        $this->assertContains('classAuth', $this->getRuleMiddlewares('admin/public'));
    }

    /**
     * 测试方法级中间件注解叠加在类级中间件之上
     */
    public function testMethodLevelMiddlewareStacksOnClassLevel()
    {
        $scanner = new AttributeRoute($this->app, $this->route);
        $scanner->scan([__DIR__ . '/stub/controller']);

        // users 路由应同时有类级 classAuth 和方法级 adminOnly
        $middlewares = $this->getRuleMiddlewares('admin/users');
        $this->assertContains('classAuth', $middlewares);
        $this->assertContains('adminOnly', $middlewares);

        // 类级中间件应在方法级之前执行
        $classPos  = array_search('classAuth', $middlewares);
        $methodPos = array_search('adminOnly', $middlewares);
        $this->assertLessThan($methodPos, $classPos);
    }

    /**
     * 测试无中间件注解的路由没有中间件
     */
    public function testRouteWithoutMiddlewareAnnotationHasNoMiddleware()
    {
        $scanner = new AttributeRoute($this->app, $this->route);
        $scanner->scan([__DIR__ . '/stub/controller']);

        // hello 路由（PlainAttributeController）没有中间件注解
        $this->assertEmpty($this->getRuleMiddlewares('hello'));
    }
}
