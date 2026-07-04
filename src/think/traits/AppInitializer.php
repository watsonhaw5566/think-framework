<?php

declare(strict_types=1);

namespace think\traits;

use think\event\AppInit;
use think\initializer\BootService;
use think\initializer\Error;
use think\initializer\RegisterService;

trait AppInitializer
{
    /**
     * 应用初始化器.
     *
     * @var array
     */
    protected $initializers = [
        Error::class,
        RegisterService::class,
        BootService::class,
    ];

    /**
     * 加载环境变量定义.
     *
     * @param string $envName 环境标识
     */
    public function loadEnv(string $envName = ''): void
    {
        $envFile = $envName ? $this->rootPath . '.env.' . $envName : $this->rootPath . '.env';

        if (is_file($envFile)) {
            $this->env->load($envFile);
        }
    }

    /**
     * 初始化应用.
     *
     * @return $this
     */
    public function initialize()
    {
        $this->initialized = true;
        $this->beginTime   = microtime(true);
        $this->beginMem    = memory_get_usage();

        if ($this->baseEnvName) {
            $this->loadEnv($this->baseEnvName);
        }

        $this->envName = $this->envName ?: (string) $this->env->get('env_name', '');
        $this->loadEnv($this->envName);

        $this->configExt = $this->env->get('config_ext', '.php');

        $this->debugModeInit();

        $this->load();

        $this->loadLangPack();

        $this->event->trigger(AppInit::class);

        date_default_timezone_set($this->config->get('app.default_timezone', 'Asia/Shanghai'));

        foreach ($this->initializers as $initializer) {
            $this->make($initializer)->init($this);
        }

        return $this;
    }

    /**
     * 是否初始化过.
     *
     * @return bool
     */
    public function initialized()
    {
        return $this->initialized;
    }

    /** 加载语言包. */
    public function loadLangPack(): void
    {
        $langSet = $this->lang->defaultLangSet();
        $this->lang->switchLangSet($langSet);
    }

    /** 加载应用文件和配置. */
    protected function load(): void
    {
        $appPath = $this->getAppPath();

        if (is_file($appPath . 'common.php')) {
            include_once $appPath . 'common.php';
        }

        include_once $this->thinkPath . 'helper.php';

        if (is_file($this->runtimePath . 'config.php')) {
            $this->config->set(include $this->runtimePath . 'config.php');
        } else {
            $this->loadConfig();
        }

        if (is_file($appPath . 'event.php')) {
            $this->loadEvent(include $appPath . 'event.php');
        }

        if (is_file($appPath . 'service.php')) {
            $services = include $appPath . 'service.php';
            foreach ($services as $service) {
                $this->register($service);
            }
        }
    }

    /** 加载配置文件. */
    public function loadConfig()
    {
        $configPath = $this->getConfigPath();
        $files      = [];

        if (is_dir($configPath)) {
            $files = glob($configPath . '*' . $this->configExt);
        }

        foreach ($files as $file) {
            $this->config->load($file, pathinfo($file, PATHINFO_FILENAME));
        }
    }

    /** 调试模式设置. */
    protected function debugModeInit(): void
    {
        if (!$this->appDebug) {
            $this->appDebug = $this->env->get('app_debug') ? true : false;
        }

        if (!$this->appDebug) {
            ini_set('display_errors', 'Off');
        }

        if (!$this->runningInConsole()) {
            if (ob_get_level() > 0) {
                $output = ob_get_clean();
            }
            ob_start();
            if (!empty($output)) {
                echo $output;
            }
        }
    }

    /**
     * 注册应用事件.
     *
     * @param array $event 事件数据
     */
    public function loadEvent(array $event): void
    {
        if (isset($event['bind'])) {
            $this->event->bind($event['bind']);
        }

        if (isset($event['listen'])) {
            $this->event->listenEvents($event['listen']);
        }

        if (isset($event['subscribe'])) {
            $this->event->subscribe($event['subscribe']);
        }
    }

    /**
     * 解析应用类名（支持多模块）.
     *
     * @param string $layer  层名 controller model ...
     * @param string $name   类名
     * @param string $module 模块名
     */
    public function parseClass(string $layer, string $name, ?string $module = ''): string
    {
        $module = $module ? $module . '\\' : '';
        if ($this->config->get('route.multi_module')) {
            $layer = $module . $layer;
        } else {
            $name = $module . $name;
        }
        $name  = str_replace(['/', '.'], '\\', $name);
        $array = explode('\\', $name);
        $class = \think\helper\Str::studly(array_pop($array));
        $path  = $array ? implode('\\', $array) . '\\' : '';

        return $this->namespace . '\\' . $layer . '\\' . $path . $class;
    }
}
