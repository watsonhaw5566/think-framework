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

    public function testYamlFile()
    {
        $root        = vfsStream::setup();
        $yamlContent = <<<YAML
key1: value1
key2: value2
YAML;
        $envFile = vfsStream::newFile('.env.yml')->setContent($yamlContent);
        $root->addChild($envFile);

        $env = new Env();
        $env->load($envFile->url());

        $this->assertEquals('value1', $env->get('key1'));
        $this->assertEquals('value2', $env->get('key2'));
    }

    public function testYamlNestedStructure()
    {
        $root        = vfsStream::setup();
        $yamlContent = <<<YAML
database:
  host: localhost
  port: 3306
  user: root
YAML;
        $envFile = vfsStream::newFile('.env.yaml')->setContent($yamlContent);
        $root->addChild($envFile);

        $env = new Env();
        $env->load($envFile->url());

        $this->assertEquals('localhost', $env->get('database.host'));
        $this->assertEquals(3306, $env->get('database.port'));
        $this->assertEquals('root', $env->get('database.user'));
    }

    public function testYamlNativeTypes()
    {
        $root        = vfsStream::setup();
        $yamlContent = <<<YAML
debug: true
cache: false
port: 6379
rate: 3.14
status: "true"
YAML;
        $envFile = vfsStream::newFile('.env.yml')->setContent($yamlContent);
        $root->addChild($envFile);

        $env = new Env();
        $env->load($envFile->url());

        $this->assertTrue($env->get('debug'));
        $this->assertFalse($env->get('cache'));
        $this->assertSame(6379, $env->get('port'));
        $this->assertSame(3.14, $env->get('rate'));
        $this->assertTrue($env->get('status'));
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

    public function testYamlDeepNestedStructure()
    {
        $root        = vfsStream::setup();
        $yamlContent = <<<YAML
database:
  connections:
    mysql:
      host: localhost
      port: 3306
      charset: utf8mb4
    redis:
      host: 127.0.0.1
      port: 6379
cache:
  stores:
    redis:
      driver: redis
      prefix: think_
YAML;
        $envFile = vfsStream::newFile('.env.yml')->setContent($yamlContent);
        $root->addChild($envFile);

        $env = new Env();
        $env->load($envFile->url());

        $this->assertEquals('localhost', $env->get('database.connections.mysql.host'));
        $this->assertEquals(3306, $env->get('database.connections.mysql.port'));
        $this->assertEquals('utf8mb4', $env->get('database.connections.mysql.charset'));
        $this->assertEquals('127.0.0.1', $env->get('database.connections.redis.host'));
        $this->assertEquals(6379, $env->get('database.connections.redis.port'));
        $this->assertEquals('redis', $env->get('cache.stores.redis.driver'));
        $this->assertEquals('think_', $env->get('cache.stores.redis.prefix'));
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
