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

use think\traits\AppConfig;
use think\traits\AppInitializer;
use think\traits\AppService;

/**
 * App 基础类.
 *
 * @property Route      $route
 * @property Config     $config
 * @property Cache      $cache
 * @property Request    $request
 * @property Http       $http
 * @property Console    $console
 * @property Env        $env
 * @property Event      $event
 * @property Middleware $middleware
 * @property Log        $log
 * @property Lang       $lang
 * @property Db         $db
 * @property Cookie     $cookie
 * @property Session    $session
 * @property Validate   $validate
 */
class App extends Container
{
    use AppConfig;
    use AppService;
    use AppInitializer;

    /**
     * 核心框架版本.
     *
     * @deprecated 已经废弃 请改用version()方法
     */
    public const VERSION = '8.0.0';

    /**
     * 应用调试模式.
     *
     * @var bool
     */
    protected $appDebug = false;

    /**
     * 公共环境变量标识.
     *
     * @var string
     */
    protected $baseEnvName = '';

    /**
     * 环境变量标识.
     *
     * @var string
     */
    protected $envName = '';

    /**
     * 应用开始时间.
     *
     * @var float
     */
    protected $beginTime;

    /**
     * 应用内存初始占用.
     *
     * @var int
     */
    protected $beginMem;

    /**
     * 当前应用类库命名空间.
     *
     * @var string
     */
    protected $namespace = 'app';

    /**
     * 应用根目录.
     *
     * @var string
     */
    protected $rootPath = '';

    /**
     * 框架目录.
     *
     * @var string
     */
    protected $thinkPath = '';

    /**
     * 应用目录.
     *
     * @var string
     */
    protected $appPath = '';

    /**
     * Runtime目录.
     *
     * @var string
     */
    protected $runtimePath = '';

    /**
     * 路由定义目录.
     *
     * @var string
     */
    protected $routePath = '';

    /**
     * 配置后缀
     *
     * @var string
     */
    protected $configExt = '.php';

    /**
     * 注册的系统服务
     *
     * @var array
     */
    protected $services = [];

    /**
     * 初始化.
     *
     * @var bool
     */
    protected $initialized = false;

    /**
     * 容器绑定标识.
     *
     * @var array
     */
    protected $bind = [
        'app'                     => App::class,
        'cache'                   => Cache::class,
        'config'                  => Config::class,
        'console'                 => Console::class,
        'cookie'                  => Cookie::class,
        'db'                      => Db::class,
        'env'                     => Env::class,
        'event'                   => Event::class,
        'http'                    => Http::class,
        'lang'                    => Lang::class,
        'log'                     => Log::class,
        'middleware'              => Middleware::class,
        'request'                 => Request::class,
        'response'                => Response::class,
        'route'                   => Route::class,
        'session'                 => Session::class,
        'validate'                => Validate::class,
        'view'                    => View::class,
        'think\DbManager'         => Db::class,
        'think\LogManager'        => Log::class,
        'think\CacheManager'      => Cache::class,

        // 接口依赖注入
        'Psr\Log\LoggerInterface' => Log::class,
    ];

    /**
     * 架构方法.
     *
     * @param string $rootPath 应用根目录
     */
    public function __construct(string $rootPath = '')
    {
        $this->thinkPath   = realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR;
        $this->rootPath    = $rootPath ? rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : $this->getDefaultRootPath();
        $this->appPath     = $this->rootPath . 'app' . DIRECTORY_SEPARATOR;
        $this->runtimePath = $this->rootPath . 'runtime' . DIRECTORY_SEPARATOR;

        if (is_file($this->appPath . 'provider.php')) {
            $this->bind(include $this->appPath . 'provider.php');
        }

        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance('think\Container', $this);
    }
}
