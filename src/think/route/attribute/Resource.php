<?php

declare (strict_types=1);

namespace think\route\attribute;

use Attribute;

/**
 * 资源路由注解
 *
 * 标注在控制器类上，为该控制器所有路由方法添加统一前缀。
 *
 * 用法：
 *   #[Resource('users')]
 *   class UserController { ... }
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Resource
{
    public function __construct(
        /** 资源路由前缀 */
        public string $rule = '',
    ) {
    }
}
