<?php

declare (strict_types=1);

namespace think\tests\stub\controller;

use think\route\attribute\Middleware;
use think\route\attribute\Resource;
use think\route\attribute\Route;

/**
 * 中间件注解测试控制器
 *
 * 类级中间件对所有路由生效
 */
#[Resource('admin')]
#[Middleware('classAuth')]
class MiddlewareAttributeController
{
    #[Route('/dashboard')]
    public function dashboard(): string
    {
        return 'dashboard';
    }

    #[Route('/users')]
    #[Middleware('adminOnly')]
    public function users(): string
    {
        return 'users';
    }

    #[Route('/public')]
    public function publicAction(): string
    {
        return 'public';
    }
}
