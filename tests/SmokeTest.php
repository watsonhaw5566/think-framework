<?php

namespace think\tests;

use Mockery as m;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use stdClass;
use think\App;
use think\Cache;
use think\Config;
use think\Container;
use think\Env;
use think\Event;
use think\Request;
use think\Route;
use think\Service;

/**
 * 框架冒烟测试.
 *
 * 用于验证每次改动后框架仍能正常工作。
 * 覆盖：容器绑定/解析、Request 基本用法、Route 注册和匹配、
 *        Cache 读写、Service 注册和启动。
 *
 * @internal
 *
 * @coversNothing
 */
class SmokeTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * 容器绑定与解析.
     */
    public function testContainerBindAndResolve()
    {
        $app = new App();

        // 1. 验证自身可以 make 出来
        $this->assertInstanceOf(App::class, $app->make(App::class));

        // 2. bound() 检查
        $this->assertFalse($app->bound('foo'));

        // 3. bind() + make()
        $app->bind('smoke.test', stdClass::class);
        $this->assertTrue($app->bound('smoke.test'));
        $this->assertInstanceOf(stdClass::class, $app->make('smoke.test'));

        // 4. instance() 直接注册
        $obj        = new stdClass();
        $obj->value = 42;
        $app->instance('smoke.obj', $obj);
        $resolved = $app->make('smoke.obj');
        $this->assertSame($obj, $resolved);
        $this->assertSame(42, $resolved->value);
    }

    /**
     * Request 基本用法.
     */
    public function testRequestBasics()
    {
        $app = m::mock(App::class)->makePartial();
        Container::setInstance($app);
        $app->shouldReceive('make')->with(App::class)->andReturn($app);
        $app->shouldReceive('isDebug')->andReturnTrue();

        $config = m::mock(Config::class)->makePartial();
        $config->shouldReceive('get')->with('app.show_error_msg')->andReturnTrue();
        $config->shouldReceive('get')->with('app.default_ajax')->andReturn('');
        $app->shouldReceive('get')->with('config')->andReturn($config);
        $app->shouldReceive('runningInConsole')->andReturn(false);

        $_GET    = ['id' => '123', 'name' => 'thinkphp'];
        $_POST   = ['title' => 'hello'];
        $_SERVER = [
            'REQUEST_METHOD' => 'GET',
            'HTTP_HOST'      => 'localhost',
            'REQUEST_URI'    => '/test?id=123&name=thinkphp',
            'SERVER_NAME'    => 'localhost',
            'SERVER_PORT'    => 80,
            'HTTPS'          => '',
            'PATH_INFO'      => '',
        ];

        $request = new Request();
        $request->withGet($_GET)->withPost($_POST);

        // param() 能获取参数
        $this->assertSame('123', $request->param('id'));
        $this->assertSame('thinkphp', $request->param('name'));
        $this->assertSame('default', $request->param('not_exist', 'default'));
        $this->assertNull($request->param('not_exist'));

        // 方法判断
        $this->assertTrue($request->isGet());
        $this->assertFalse($request->isPost());
        $this->assertSame('GET', $request->method());

        // 域名
        $this->assertNotEmpty($request->domain());
    }

    /**
     * Route 注册和匹配.
     */
    public function testRouteRegisterAndMatch()
    {
        $app = m::mock(App::class)->makePartial();
        Container::setInstance($app);
        $app->shouldReceive('make')->with(App::class)->andReturn($app);
        $app->shouldReceive('isDebug')->andReturnTrue();

        $config = m::mock(Config::class)->makePartial();
        $config->shouldReceive('get')->with('app.show_error_msg')->andReturnTrue();
        $config->shouldReceive('get')->with('route')->andReturn([
            'url_route_must' => false,
        ]);
        $app->shouldReceive('get')->with('config')->andReturn($config);
        $app->shouldReceive('runningInConsole')->andReturn(false);

        $route = new Route($app);

        $route->get('hello', function () {
            return 'world';
        });

        $request = m::mock(Request::class)->makePartial();
        $request->shouldReceive('host')->andReturn('localhost');
        $request->shouldReceive('pathinfo')->andReturn('hello');
        $request->shouldReceive('url')->andReturn('/hello');
        $request->shouldReceive('method')->andReturn('GET');

        $dispatch = $route->dispatch($request);
        $this->assertNotEmpty($dispatch);
    }

    /**
     * Cache 读写（遵循 CacheTest 的模式）.
     */
    public function testCacheReadWrite()
    {
        $app = m::mock(App::class)->makePartial();
        Container::setInstance($app);
        $app->shouldReceive('make')->with(App::class)->andReturn($app);

        $config = m::mock(Config::class)->makePartial();
        $app->shouldReceive('get')->with('config')->andReturn($config);

        $cacheDir = sys_get_temp_dir() . '/think_smoke_test_' . uniqid();
        @mkdir($cacheDir, 0755, true);

        // 模拟 Cache 实际访问的点分配置键
        $config->shouldReceive('get')->with('cache.default', null)->andReturn('file');
        $config->shouldReceive('get')->with('cache.stores.file', null)
            ->andReturn(['type' => 'File', 'path' => $cacheDir])
        ;

        $cache = new Cache($app);

        // 写入
        $this->assertTrue($cache->set('smoke_key', 'smoke_value', 60));

        // 读取
        $this->assertSame('smoke_value', $cache->get('smoke_key'));

        // has()
        $this->assertTrue($cache->has('smoke_key'));
        $this->assertFalse($cache->has('smoke_not_exist'));

        // 默认值
        $this->assertSame('default_val', $cache->get('smoke_not_exist', 'default_val'));

        // 删除
        $this->assertTrue($cache->delete('smoke_key'));
        $this->assertFalse($cache->has('smoke_key'));

        // 自增/自减
        $cache->set('smoke_counter', 10, 60);
        $this->assertSame(11, $cache->inc('smoke_counter'));
        $this->assertSame(10, $cache->dec('smoke_counter'));

        $this->removeDir($cacheDir);
    }

    /**
     * Service 注册和启动.
     */
    public function testServiceRegisterAndBoot()
    {
        $app = new App();

        $service = new class($app) extends Service {
            public bool $registered = false;
            public bool $booted     = false;

            public function register()
            {
                $this->registered = true;
                $this->app->instance('smoke.service.token', 'registered');
            }

            public function boot()
            {
                $this->booted = true;
                $this->app->instance('smoke.boot.token', 'booted');
            }
        };

        $app->register($service);
        $this->assertTrue($service->registered);
        $this->assertSame('registered', $app->make('smoke.service.token'));
        $this->assertFalse($service->booted);

        $app->boot();
        $this->assertTrue($service->booted);
        $this->assertSame('booted', $app->make('smoke.boot.token'));
    }

    /**
     * 框架初始化验证
     *
     * 模拟 AppTest 的做法：替换 initializers 列表，只测试我们关心的部分
     */
    public function testFrameworkInitialization()
    {
        $root = vfsStream::setup('rootDir', null, [
            '.env'   => '',
            'app'    => [
                'common.php'   => '',
                'event.php'    => '<?php return ["bind"=>[],"listen"=>[],"subscribe"=>[]];',
                'provider.php' => '<?php return [];',
            ],
            'config' => [
                'app.php' => '<?php return [];',
            ],
        ]);

        $rootPath = $root->url() . DIRECTORY_SEPARATOR;
        $app      = new App($rootPath);

        // 用一个简单的 initializer 替换默认列表，避免触发复杂的服务启动
        $initializer = m::mock();
        $initializer->shouldReceive('init')->once()->with($app);
        $app->instance(\get_class($initializer), $initializer);

        (function () use ($initializer) {
            $this->initializers = [\get_class($initializer)];
        })->call($app);

        $env = m::mock(Env::class);
        $env->shouldReceive('load')->andReturn();
        $env->shouldReceive('get')->with('config_ext', '.php')->andReturn('.php');
        $env->shouldReceive('get')->with('app_debug')->andReturn(true);
        $env->shouldReceive('get')->with('env_name', '')->andReturn('');
        $env->shouldReceive('get')->andReturn(null)->byDefault();

        $event = m::mock(Event::class);
        $event->shouldReceive('trigger')->andReturn();
        $event->shouldReceive('bind')->andReturn();
        $event->shouldReceive('listenEvents')->andReturn();
        $event->shouldReceive('subscribe')->andReturn();

        $app->instance('env', $env);
        $app->instance('event', $event);
        $app->debug(true);

        $app->initialize();

        $this->assertTrue($app->initialized());
        $this->assertIsFloat($app->getBeginTime());
        $this->assertIsInt($app->getBeginMem());
    }

    /**
     * App 仍然是 Container 实例（向后兼容验证）.
     */
    public function testAppIsStillContainerInstance()
    {
        $app = new App();

        $this->assertInstanceOf(Container::class, $app);
        $this->assertTrue($app instanceof Container);
    }

    /**
     * 删除临时目录.
     */
    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
