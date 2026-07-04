<?php

namespace think\tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use think\Route;

class RouteTraitsTest extends TestCase
{
    use InteractsWithApp;

    protected function setUp(): void
    {
        $this->prepareApp();
        $this->config->shouldReceive('get')->with('route')->andReturn(['url_route_must' => false]);
    }

    protected function tearDown(): void
    {
        m::close();
    }

    protected function makeRequest($path, $method = 'GET', $host = 'localhost')
    {
        $request = m::mock(\think\Request::class)->makePartial();
        $request->shouldReceive('host')->andReturn($host);
        $request->shouldReceive('host')->with(true)->andReturn($host);
        $request->shouldReceive('pathinfo')->andReturn($path);
        $request->shouldReceive('url')->andReturn('/' . $path);
        $request->shouldReceive('method')->andReturn(strtoupper($method));
        $request->shouldReceive('subDomain')->andReturn('');
        $request->shouldReceive('ext')->andReturn('');

        return $request;
    }

    public function testRouteRegisterTrait()
    {
        $route = new Route($this->app);

        $route->get('get-test', function () {
            return 'get-result';
        });

        $route->post('post-test', function () {
            return 'post-result';
        });

        $route->put('put-test', function () {
            return 'put-result';
        });

        $route->delete('delete-test', function () {
            return 'delete-result';
        });

        $route->patch('patch-test', function () {
            return 'patch-result';
        });

        $route->head('head-test', function () {
            return 'head-result';
        });

        $route->options('options-test', function () {
            return 'options-result';
        });

        $route->any('any-test', function () {
            return 'any-result';
        });

        $request  = $this->makeRequest('get-test', 'get');
        $response = $route->dispatch($request);
        $this->assertEquals('get-result', $response->getContent());

        $request  = $this->makeRequest('any-test', 'post');
        $response = $route->dispatch($request);
        $this->assertEquals('any-result', $response->getContent());
    }

    public function testRouteGroupTrait()
    {
        $route = new Route($this->app);

        $route->group('api', function () use ($route) {
            $route->get('users', function () {
                return 'users-list';
            });

            $route->group('v1', function () use ($route) {
                $route->get('items', function () {
                    return 'v1-items';
                });
            });
        });

        $request  = $this->makeRequest('api/users', 'get');
        $response = $route->dispatch($request);
        $this->assertEquals('users-list', $response->getContent());

        $request  = $this->makeRequest('api/v1/items', 'get');
        $response = $route->dispatch($request);
        $this->assertEquals('v1-items', $response->getContent());
    }

    public function testRouteGroupPatternAndOption()
    {
        $route = new Route($this->app);

        $route->pattern(['id' => '\d+']);

        $route->get('user/<id>', function ($id) {
            return "user-$id";
        });

        $request  = $this->makeRequest('user/123', 'get');
        $response = $route->dispatch($request);
        $this->assertEquals('user-123', $response->getContent());
    }

    public function testRouteResourceTrait()
    {
        $route = new Route($this->app);

        $resource = $route->resource('photos', 'Photo');
        $this->assertNotNull($resource);

        $rest = $route->getRest();
        $this->assertIsArray($rest);
        $this->assertArrayHasKey('index', $rest);
        $this->assertArrayHasKey('create', $rest);
        $this->assertArrayHasKey('read', $rest);
        $this->assertArrayHasKey('update', $rest);
        $this->assertArrayHasKey('delete', $rest);

        $route->rest('custom', ['get', '/custom', 'custom']);
        $customRest = $route->getRest('custom');
        $this->assertEquals(['get', '/custom', 'custom'], $customRest);
    }

    public function testRouteDomainTrait()
    {
        $route = new Route($this->app);

        $route->domain('api.example.com', function () use ($route) {
            $route->get('users', function () {
                return 'api-users';
            });
        });

        $route->domain(['admin.example.com', 'admin2.example.com'], function () use ($route) {
            $route->get('dashboard', function () {
                return 'admin-dashboard';
            });
        });

        $request = $this->makeRequest('users', 'get', 'api.example.com');
        $request->shouldReceive('subDomain')->andReturn('api');
        $response = $route->dispatch($request);
        $this->assertEquals('api-users', $response->getContent());

        $domains = $route->getDomains();
        $this->assertIsArray($domains);
        $this->assertArrayHasKey('-', $domains);
        $this->assertArrayHasKey('api.example.com', $domains);
    }

    public function testRouteDispatchTrait()
    {
        $route = new Route($this->app);

        $route->get('hello', function () {
            return 'hello-world';
        });

        $request  = $this->makeRequest('hello', 'get');
        $response = $route->dispatch($request);

        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('hello-world', $response->getContent());
    }

    public function testRouteUrlTrait()
    {
        $route = new Route($this->app);

        $route->get('user/<id>', 'User/detail')->name('user.detail');

        $url = $route->buildUrl('user.detail', ['id' => 456]);
        $this->assertInstanceOf(\think\route\Url::class, $url);
    }

    public function testMissRoute()
    {
        $route = new Route($this->app);

        $route->get('home', function () {
            return 'home-page';
        });

        $route->miss(function () {
            return 'miss-handler';
        });

        $request  = $this->makeRequest('nonexistent', 'get');
        $response = $route->dispatch($request);
        $this->assertEquals('miss-handler', $response->getContent());
    }

    public function testViewRoute()
    {
        $route = new Route($this->app);

        $route->view('page', 'template', ['title' => 'Test']);

        $request  = $this->makeRequest('page', 'get');
        $response = $route->dispatch($request);

        $this->assertInstanceOf(\think\response\View::class, $response);
        $this->assertEquals('template', $response->getData());
    }

    public function testRedirectRoute()
    {
        $route = new Route($this->app);

        $route->redirect('old', '/new', 301);

        $request = $this->makeRequest('old', 'get');
        $this->app->shouldReceive('make')->with(\think\Request::class)->andReturn($request);
        $response = $route->dispatch($request);

        $this->assertInstanceOf(\think\response\Redirect::class, $response);
        $this->assertEquals(301, $response->getCode());
    }

    public function testRouteAuto()
    {
        $route = new Route($this->app);
        $route->auto();

        $request  = $this->makeRequest('index/Index/index', 'get');
        $response = $route->dispatch($request);

        $this->assertNotNull($response);
    }

    public function testRouteMergeRuleRegex()
    {
        $route = new Route($this->app);
        $route->mergeRuleRegex(true);

        $route->get('user/<id>', function ($id) {
            return "user-$id";
        });

        $request  = $this->makeRequest('user/789', 'get');
        $response = $route->dispatch($request);
        $this->assertEquals('user-789', $response->getContent());
    }

    public function testRouteLazy()
    {
        $route = new Route($this->app);
        $route->lazy(true);

        $resource = $route->resource('posts', 'Post');
        $this->assertInstanceOf(\think\route\Resource::class, $resource);
    }
}
