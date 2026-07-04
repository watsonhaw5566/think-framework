<?php

declare(strict_types=1);

namespace think\traits;

use think\Service;

trait AppService
{
    /**
     * 注册服务
     *
     * @param Service|string $service 服务
     * @param bool           $force   强制重新注册
     *
     * @return null|Service
     */
    public function register(Service|string $service, bool $force = false)
    {
        $registered = $this->getService($service);

        if ($registered && !$force) {
            return $registered;
        }

        if (is_string($service)) {
            $service = new $service($this);
        }

        if (method_exists($service, 'register')) {
            $service->register();
        }

        if (property_exists($service, 'bind')) {
            $this->bind($service->bind);
        }

        $this->services[] = $service;

        return $service;
    }

    /**
     * 执行服务
     *
     * @param Service $service 服务
     *
     * @return mixed
     */
    public function bootService(Service $service)
    {
        if (method_exists($service, 'boot')) {
            return $this->invoke([$service, 'boot']);
        }
    }

    /** 获取服务 */
    public function getService(Service|string $service): ?Service
    {
        $name = is_string($service) ? $service : $service::class;

        return array_values(array_filter($this->services, function ($value) use ($name) {
            return $value instanceof $name;
        }, ARRAY_FILTER_USE_BOTH))[0] ?? null;
    }

    /** 引导应用. */
    public function boot(): void
    {
        array_walk($this->services, function ($service) {
            $this->bootService($service);
        });
    }
}
