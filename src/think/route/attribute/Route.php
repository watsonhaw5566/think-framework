<?php

declare (strict_types=1);

namespace think\route\attribute;

use Attribute;

/**
 * 路由注解
 *
 * 用法：
 *   #[Route('/')]              // 默认 GET
 *   #[Route('/{id}')]          // 默认 GET
 *   #[Route('/', 'POST')]      // 指定 POST
 *   #[Route('/', 'PUT')]       // 指定 PUT
 *   #[Route('/', '*')]         // 匹配所有方法
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{
    public function __construct(
        /** 路由规则 */
        public string $rule = '',
        /** 请求类型，默认为 GET */
        public string $method = 'GET',
    ) {
    }
}
