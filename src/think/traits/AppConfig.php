<?php

declare(strict_types=1);

namespace think\traits;

use Throwable;

trait AppConfig
{
    /**
     * 开启应用调试模式.
     *
     * @param bool $debug 开启应用调试模式
     *
     * @return $this
     */
    public function debug(bool $debug = true)
    {
        $this->appDebug = $debug;

        return $this;
    }

    /** 是否为调试模式. */
    public function isDebug(): bool
    {
        return $this->appDebug;
    }

    /**
     * 设置应用命名空间.
     *
     * @param string $namespace 应用命名空间
     *
     * @return $this
     */
    public function setNamespace(string $namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }

    /** 获取应用类库命名空间. */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * 设置公共环境变量标识.
     *
     * @param string $name 环境标识
     *
     * @return $this
     */
    public function setBaseEnvName(string $name)
    {
        $this->baseEnvName = $name;

        return $this;
    }

    /**
     * 设置环境变量标识.
     *
     * @param string $name 环境标识
     *
     * @return $this
     */
    public function setEnvName(string $name)
    {
        $this->envName = $name;

        return $this;
    }

    /** 获取框架版本. */
    public function version(): string
    {
        try {
            return ltrim(\Composer\InstalledVersions::getPrettyVersion('topthink/framework'), 'v');
        } catch (Throwable $e) {
            return '8.0.0';
        }
    }

    /** 获取应用根目录. */
    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    /** 获取应用基础目录. */
    public function getBasePath(): string
    {
        return $this->rootPath . 'app' . DIRECTORY_SEPARATOR;
    }

    /** 获取当前应用目录. */
    public function getAppPath(): string
    {
        return $this->appPath;
    }

    /**
     * 设置应用目录.
     *
     * @param string $path 应用目录
     */
    public function setAppPath(string $path)
    {
        $this->appPath = $path;
    }

    /** 获取应用运行时目录. */
    public function getRuntimePath(): string
    {
        return $this->runtimePath;
    }

    /**
     * 设置runtime目录.
     *
     * @param string $path 定义目录
     */
    public function setRuntimePath(string $path): void
    {
        $this->runtimePath = $path;
    }

    /** 获取核心框架目录. */
    public function getThinkPath(): string
    {
        return $this->thinkPath;
    }

    /** 获取应用配置目录. */
    public function getConfigPath(): string
    {
        return $this->rootPath . 'config' . DIRECTORY_SEPARATOR;
    }

    /** 获取配置后缀 */
    public function getConfigExt(): string
    {
        return $this->configExt;
    }

    /** 获取应用开启时间. */
    public function getBeginTime(): float
    {
        return $this->beginTime;
    }

    /** 获取应用初始内存占用. */
    public function getBeginMem(): int
    {
        return $this->beginMem;
    }

    /** 是否运行在命令行下. */
    public function runningInConsole(): bool
    {
        return 'cli' === php_sapi_name() || 'phpdbg' === php_sapi_name();
    }

    /** 获取应用根目录. */
    protected function getDefaultRootPath(): string
    {
        return dirname($this->thinkPath, 4) . DIRECTORY_SEPARATOR;
    }
}
