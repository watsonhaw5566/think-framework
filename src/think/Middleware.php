<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2025 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Slince <taosikai@yeah.net>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace think;

use Closure;
use LogicException;
use think\exception\Handle;
use Throwable;

/**
 * 中间件管理类.
 */
class Middleware
{
    /**
     * 中间件执行队列.
     *
     * @var array
     */
    protected $queue = [];

    public function __construct(protected App $app) {}

    /**
     * 导入中间件.
     *
     * @param string $type 中间件类型
     */
    public function import(array $middlewares = [], string $type = 'global'): void
    {
        foreach ($middlewares as $middleware) {
            $this->add($middleware, $type);
        }
    }

    /**
     * 注册中间件.
     *
     * @param mixed  $middleware
     * @param string $type       中间件类型
     */
    public function add(array|Closure|string $middleware, string $type = 'global'): void
    {
        $middleware = $this->buildMiddleware($middleware, $type);

        if (!empty($middleware)) {
            $this->queue[$type][] = $middleware;
            $this->queue[$type]   = array_unique($this->queue[$type], SORT_REGULAR);
        }
    }

    /**
     * 注册路由中间件.
     *
     * @param mixed $middleware
     */
    public function route(array|Closure|string $middleware): void
    {
        $this->add($middleware, 'route');
    }

    /**
     * 注册控制器中间件.
     *
     * @param mixed $middleware
     */
    public function controller(array|Closure|string $middleware): void
    {
        $this->add($middleware, 'controller');
    }

    /**
     * 注册中间件到开始位置.
     *
     * @param mixed  $middleware
     * @param string $type       中间件类型
     */
    public function unshift(array|Closure|string $middleware, string $type = 'global')
    {
        $middleware = $this->buildMiddleware($middleware, $type);

        if (!empty($middleware)) {
            if (!isset($this->queue[$type])) {
                $this->queue[$type] = [];
            }

            array_unshift($this->queue[$type], $middleware);
        }
    }

    /**
     * 获取注册的中间件.
     *
     * @param string $type 中间件类型
     */
    public function all(string $type = 'global'): array
    {
        return $this->queue[$type] ?? [];
    }

    /**
     * 调度管道.
     *
     * @param string $type 中间件类型
     *
     * @return Pipeline
     */
    public function pipeline(string $type = 'global')
    {
        return (new Pipeline())
            ->through(array_map(function ($middleware) {
                return function ($request, $next) use ($middleware) {
                    [$call, $params] = $middleware;
                    if (is_array($call) && is_string($call[0])) {
                        $call = [$this->app->make($call[0]), $call[1]];
                    }
                    $response = call_user_func($call, $request, $next, ...$params);

                    if (!$response instanceof Response) {
                        throw new LogicException('The middleware must return Response instance');
                    }

                    return $response;
                };
            }, $this->sortMiddleware($this->queue[$type] ?? [])))
            ->whenException([$this, 'handleException'])
        ;
    }

    /**
     * 结束调度.
     */
    public function end(Response $response)
    {
        foreach ($this->queue as $queue) {
            foreach ($queue as $middleware) {
                [$call] = $middleware;
                if (is_array($call) && is_string($call[0])) {
                    $instance = $this->app->make($call[0]);
                    if (method_exists($instance, 'end')) {
                        $instance->end($response);
                    }
                }
            }
        }
    }

    /**
     * 异常处理.
     *
     * @param Request $passable
     *
     * @return Response
     */
    public function handleException($passable, Throwable $e)
    {
        /** @var Handle $handler */
        $handler = $this->app->make(Handle::class);

        $handler->report($e);

        return $handler->render($passable, $e);
    }

    /**
     * 解析中间件.
     *
     * @param string $type 中间件类型
     */
    protected function buildMiddleware(array|Closure|string $middleware, string $type): array
    {
        if (empty($middleware)) {
            return [];
        }

        if (is_array($middleware)) {
            [$middleware, $params] = $middleware;
        }

        if ($middleware instanceof Closure) {
            return [$middleware, $params ?? []];
        }

        // 中间件别名检查
        $alias = $this->app->config->get('middleware.alias', []);

        if (isset($alias[$middleware])) {
            $middleware = $alias[$middleware];
        }

        if (is_array($middleware)) {
            $this->import($middleware, $type);

            return [];
        }

        return [[$middleware, 'handle'], $params ?? []];
    }

    /**
     * 中间件排序.
     *
     * @return array
     */
    protected function sortMiddleware(array $middlewares)
    {
        $priority = $this->app->config->get('middleware.priority', []);
        uasort($middlewares, function ($a, $b) use ($priority) {
            $aPriority = $this->getMiddlewarePriority($priority, $a);
            $bPriority = $this->getMiddlewarePriority($priority, $b);

            return $bPriority - $aPriority;
        });

        return $middlewares;
    }

    /**
     * 获取中间件优先级.
     *
     * @return int
     */
    protected function getMiddlewarePriority($priority, $middleware)
    {
        [$call] = $middleware;
        if (is_array($call) && is_string($call[0])) {
            $index = array_search($call[0], array_reverse($priority));

            return false === $index ? -1 : $index;
        }

        return -1;
    }
}
