<?php

declare (strict_types=1);

namespace think\tests\stub\controller;

use think\route\attribute\Route;

/**
 * 无前缀的注解路由测试控制器
 */
class PlainAttributeController
{
    #[Route('/hello')]
    public function hello(): string
    {
        return 'hello';
    }

    #[Route('/world', 'POST')]
    public function world(): string
    {
        return 'world';
    }
}
