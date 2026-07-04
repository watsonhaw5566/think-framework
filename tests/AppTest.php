<?php

namespace think\tests;

use PHPUnit\Framework\TestCase;
use think\App;
use think\Container;

class AppTest extends TestCase
{
    /** @var App */
    protected $app;

    protected function setUp(): void
    {
        $this->app = new App();
        Container::setInstance($this->app);
    }

    public function testAppConfigDebug()
    {
        $app = $this->app;

        $app->debug();
        $this->assertTrue($app->isDebug());

        $app->debug(false);
        $this->assertFalse($app->isDebug());
    }

    public function testAppConfigNamespace()
    {
        $app = $this->app;

        $app->setNamespace('custom');
        $this->assertEquals('custom', $app->getNamespace());
    }

    public function testAppConfigVersion()
    {
        $app = $this->app;

        $version = $app->version();
        $this->assertNotEmpty($version);
        $this->assertIsString($version);
    }

    public function testAppConfigPaths()
    {
        $app = $this->app;

        $this->assertNotEmpty($app->getRootPath());
        $this->assertNotEmpty($app->getAppPath());
        $this->assertNotEmpty($app->getRuntimePath());
        $this->assertNotEmpty($app->getThinkPath());
        $this->assertNotEmpty($app->getConfigPath());
        $this->assertEquals('.php', $app->getConfigExt());
    }

    public function testAppConfigSetPaths()
    {
        $app = $this->app;

        $app->setAppPath('/custom/app/');
        $this->assertEquals('/custom/app/', $app->getAppPath());

        $app->setRuntimePath('/custom/runtime/');
        $this->assertEquals('/custom/runtime/', $app->getRuntimePath());
    }

    public function testAppConfigRunningInConsole()
    {
        $app = $this->app;

        $result = $app->runningInConsole();
        $this->assertIsBool($result);
    }

    public function testAppServiceRegister()
    {
        $app = $this->app;

        $service    = new TestService($app);
        $registered = $app->register($service);

        $this->assertSame($service, $registered);
        $this->assertSame($service, $app->getService(TestService::class));
    }

    public function testAppServiceGetService()
    {
        $app = $this->app;

        $service = $app->getService('non.existent.class');
        $this->assertNull($service);
    }

    public function testAppServiceBoot()
    {
        $app = $this->app;

        $service = new TestService($app);
        $app->register($service);
        $app->boot();

        $this->assertTrue($service->booted);
    }

    public function testAppInitInitialized()
    {
        $app = $this->app;

        $this->assertFalse($app->initialized());
    }

    public function testAppInitLoadEnv()
    {
        $app = $this->app;

        $app->loadEnv();
    }

    public function testAppInitParseClass()
    {
        $app = $this->app;

        $class = $app->parseClass('controller', 'Index');
        $this->assertEquals('app\\controller\\Index', $class);

        $class = $app->parseClass('controller', 'api/Index');
        $this->assertEquals('app\\controller\\api\\Index', $class);
    }

    public function testAppInitParseClassWithModule()
    {
        $app = $this->app;

        $class = $app->parseClass('controller', 'Index', 'admin');
        $this->assertEquals('app\\controller\\admin\\Index', $class);
    }
}

class TestService extends \think\Service
{
    public $booted = false;

    public function boot()
    {
        $this->booted = true;
    }
}
