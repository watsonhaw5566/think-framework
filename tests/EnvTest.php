<?php

namespace think\tests;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use think\Env;
use think\Exception;

class EnvTest extends TestCase
{
    public function testEnvFile()
    {
        $root    = vfsStream::setup();
        $envFile = vfsStream::newFile('.env')->setContent("key1=value1\nkey2=value2");
        $root->addChild($envFile);

        $env = new Env();

        $env->load($envFile->url());

        $this->assertEquals('value1', $env->get('key1'));
        $this->assertEquals('value2', $env->get('key2'));
    }

    public function testServerEnv()
    {
        $env = new Env();

        $this->assertEquals('value2', $env->get('key2', 'value2'));

        putenv('PHP_KEY7=value7');
        putenv('PHP_KEY8=false');
        putenv('PHP_KEY9=true');

        $this->assertEquals('value7', $env->get('key7'));
        $this->assertFalse($env->get('KEY8'));
        $this->assertTrue($env->get('key9'));
    }

    public function testSetEnv()
    {
        $env = new Env();

        $env->set([
            'key1' => 'value1',
            'key2' => [
                'key1' => 'value1-2',
            ],
        ]);

        $env->set('key3', 'value3');

        $env->key4 = 'value4';

        $env['key5'] = 'value5';

        $this->assertEquals('value1', $env->get('key1'));
        $this->assertEquals('value1-2', $env->get('key2.key1'));

        $this->assertEquals('value3', $env->get('key3'));

        $this->assertEquals('value4', $env->key4);

        $this->assertEquals('value5', $env['key5']);

        $this->expectException(Exception::class);

        unset($env['key5']);
    }

    public function testHasEnv()
    {
        $env = new Env();
        $env->set(['foo' => 'bar']);
        $this->assertTrue($env->has('foo'));
        $this->assertTrue(isset($env->foo));
        $this->assertTrue($env->offsetExists('foo'));
    }

    public function testUnsupportedFormat()
    {
        $root    = vfsStream::setup();
        $envFile = vfsStream::newFile('.env.xml')->setContent('<root/>');
        $root->addChild($envFile);

        $env = new Env();

        $this->expectException(Exception::class);
        $env->load($envFile->url());
    }

    public function testSetEnvDeepNested()
    {
        $env = new Env();

        $env->set([
            'level1' => [
                'level2' => [
                    'level3'   => [
                        'level4' => 'deep-value',
                    ],
                    'another3' => 'value3',
                ],
            ],
        ]);

        $this->assertEquals('deep-value', $env->get('level1.level2.level3.level4'));
        $this->assertEquals('value3', $env->get('level1.level2.another3'));
    }

    public function testSetEnvIndexedArrayPreserved()
    {
        $env = new Env();

        $env->set([
            'servers' => [
                '127.0.0.1',
                '127.0.0.2',
                '127.0.0.3',
            ],
            'queue'   => [
                'connections' => ['sync', 'redis', 'database'],
            ],
        ]);

        $servers = $env->get('servers');
        $this->assertIsArray($servers);
        $this->assertEquals(['127.0.0.1', '127.0.0.2', '127.0.0.3'], $servers);

        $connections = $env->get('queue.connections');
        $this->assertIsArray($connections);
        $this->assertEquals(['sync', 'redis', 'database'], $connections);
    }

    public function testSetEnvMixedArray()
    {
        $env = new Env();

        $env->set([
            'app' => [
                'name'      => 'think',
                'debug'     => true,
                'locales'   => ['zh-cn', 'en-us'],
                'providers' => [
                    'core'  => 'CoreProvider',
                    'extra' => ['A', 'B'],
                ],
            ],
        ]);

        $this->assertEquals('think', $env->get('app.name'));
        $this->assertTrue($env->get('app.debug'));
        $this->assertEquals(['zh-cn', 'en-us'], $env->get('app.locales'));
        $this->assertEquals('CoreProvider', $env->get('app.providers.core'));
        $this->assertEquals(['A', 'B'], $env->get('app.providers.extra'));
    }
}
