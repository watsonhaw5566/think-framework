<?php

declare (strict_types=1);

namespace think\tests\stub\controller;

use think\route\attribute\Resource;
use think\route\attribute\Route;

/**
 * 注解路由测试控制器
 */
#[Resource('users')]
class AttributeUserController
{
    #[Route('/')]
    public function index(): string
    {
        return 'index';
    }

    #[Route('/{id}')]
    public function show(int $id): string
    {
        return 'show:' . $id;
    }

    #[Route('/', 'POST')]
    public function store(): string
    {
        return 'store';
    }

    #[Route('/{id}', 'PUT')]
    public function update(int $id): string
    {
        return 'update:' . $id;
    }

    // 无注解方法，不应注册路由
    public function internal(): string
    {
        return 'internal';
    }
}
