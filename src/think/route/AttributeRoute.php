<?php

declare (strict_types=1);

namespace think\route;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use think\App;
use think\Route as Router;
use think\route\attribute\Middleware as MiddlewareAttribute;
use think\route\attribute\Resource as ResourceAttribute;
use think\route\attribute\Route as RouteAttribute;

/**
 * 注解路由扫描器
 *
 * 扫描控制器目录，通过 PHP 8 反射读取类上的 #[Resource] 与方法上的 #[Route] 属性，
 * 调用框架现有的 Route::rule() 注册路由，从而复用已有的调度、参数绑定、中间件等能力。
 */
class AttributeRoute
{
    public function __construct(
        protected App $app,
        protected Router $route,
    ) {
    }

    /**
     * 扫描给定目录列表中的控制器并注册注解路由
     *
     * @param array<int, string> $paths 控制器目录绝对路径列表
     */
    public function scan(array $paths): void
    {
        foreach ($paths as $path) {
            $this->scanPath($path);
        }
    }

    /**
     * 扫描单个目录
     */
    protected function scanPath(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->getClassNameFromFile($file->getRealPath());
            if ($className === null || !class_exists($className)) {
                continue;
            }

            $this->registerRoutesFromClass($className);
        }
    }

    /**
     * 从 PHP 文件内容中解析出完整类名（命名空间 + 类名）
     */
    protected function getClassNameFromFile(string $filePath): ?string
    {
        $contents = file_get_contents($filePath);
        if ($contents === false) {
            return null;
        }

        $namespace = '';
        $class     = '';

        if (preg_match('/^\s*namespace\s+([^;]+);/m', $contents, $m)) {
            $namespace = trim($m[1]);
        }

        if (preg_match('/^\s*(?:abstract\s+|final\s+)?class\s+(\w+)/m', $contents, $m)) {
            $class = $m[1];
        }

        if ($class === '') {
            return null;
        }

        return $namespace ? $namespace . '\\' . $class : $class;
    }

    /**
     * 反射单个控制器类，读取类级 #[Resource]/#[Middleware] 与方法级 #[Route]/#[Middleware] 并注册路由
     */
    protected function registerRoutesFromClass(string $className): void
    {
        $reflection = new ReflectionClass($className);

        // 跳过抽象类
        if ($reflection->isAbstract()) {
            return;
        }

        // 类级 Resource 注解：提取统一前缀
        $prefix = '';
        foreach ($reflection->getAttributes(ResourceAttribute::class) as $attr) {
            $resource = $attr->newInstance();
            $prefix   = trim($resource->rule, '/');
            break;
        }

        // 类级 Middleware 注解：对该控制器所有路由生效
        $classMiddlewares = $this->collectMiddlewares($reflection);

        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            // 跳过构造函数和魔术方法
            if ($method->isConstructor() || str_starts_with($method->getName(), '__')) {
                continue;
            }

            $routeAttrs = $method->getAttributes(RouteAttribute::class);
            if (!$routeAttrs) {
                continue;
            }

            // 方法级 Middleware 注解：仅对当前路由生效
            $methodMiddlewares = $this->collectMiddlewares($method);

            foreach ($routeAttrs as $routeAttr) {
                $route      = $routeAttr->newInstance();
                $rule       = $route->rule;
                $httpMethod = $route->method ?: '*';

                // 拼接资源前缀：Resource('users') + Route('/{id}') => users/{id}
                if ($prefix !== '') {
                    $rule = $prefix . '/' . ltrim($rule, '/');
                }

                // 规范化首尾斜杠，与 think 内置 Resource 路由行为一致
                $rule = trim($rule, '/');

                // 使用数组形式的路由地址，走 Callback dispatch，原生支持构造函数 DI 与方法参数绑定
                // 注解路由默认完全匹配，与 think 内置 Resource 路由行为一致
                $ruleItem = $this->route->rule($rule, [$className, $method->getName()], $httpMethod)
                    ->completeMatch();

                // 应用中间件：类级优先执行，方法级次之
                foreach (array_merge($classMiddlewares, $methodMiddlewares) as $middleware) {
                    $ruleItem->middleware($middleware['middleware'], ...$middleware['params']);
                }
            }
        }
    }

    /**
     * 从反射对象上收集 Middleware 注解
     *
     * @param ReflectionClass|ReflectionMethod $reflection
     * @return array<int, array{middleware: string|array, params: array}>
     */
    protected function collectMiddlewares(ReflectionClass | ReflectionMethod $reflection): array
    {
        $middlewares = [];

        foreach ($reflection->getAttributes(MiddlewareAttribute::class) as $attr) {
            $middleware    = $attr->newInstance();
            $middlewares[] = [
                'middleware' => $middleware->middleware,
                'params'     => $middleware->params,
            ];
        }

        return $middlewares;
    }
}
