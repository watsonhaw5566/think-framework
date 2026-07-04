<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace think\request;

/**
 * 请求内容 Trait
 */
trait InteractsWithContent
{
    public function getContent(): string
    {
        if (is_null($this->content)) {
            $this->content = $this->input;
        }

        return $this->content;
    }

    public function getInput(): string
    {
        return $this->input;
    }
}
