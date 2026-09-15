<?php

declare (strict_types=1);

namespace think\route\attribute;

use Attribute;

/**
 * 中间件注解
 *
 * 可标注在控制器类或方法上。
 * - 类级：对该控制器所有路由生效
 * - 方法级：仅对当前路由生效
 *
 * 用法：
 *   #[Middleware('auth')]
 *   #[Middleware(['auth', 'admin'])]
 *   #[Middleware('auth', params: ['role' => 'admin'])]
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Middleware
{
    /**
     * @param string|array $middleware 中间件别名、类名或数组
     * @param array        $params     中间件参数
     */
    public function __construct(
        public string | array $middleware,
        public array $params = [],
    ) {
    }
}
