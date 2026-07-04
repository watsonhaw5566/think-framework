<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2025 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

namespace think\event;

use DateTimeImmutable;

/**
 * LogRecord事件类.
 */
class LogRecord
{
    public string $type;

    /** @var array|string */
    public $message;

    public DateTimeImmutable $time;

    public function __construct($type, $message)
    {
        $this->type    = $type;
        $this->message = $message;
        $this->time    = new DateTimeImmutable();
    }
}
