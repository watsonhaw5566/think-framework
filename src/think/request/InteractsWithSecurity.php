<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace think\request;

/**
 * 安全处理 Trait
 */
trait InteractsWithSecurity
{
    public function buildToken(string $name = '__token__', $type = 'md5'): string
    {
        $type  = is_callable($type) ? $type : 'md5';
        $token = call_user_func($type, $this->server('REQUEST_TIME_FLOAT'));

        $this->session->set($name, $token);

        return $token;
    }

    public function checkToken(string $token = '__token__', array $data = []): bool
    {
        if (in_array($this->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        if (!$this->session->has($token)) {
            return false;
        }

        if ($this->header('X-CSRF-TOKEN') && $this->session->get($token) === $this->header('X-CSRF-TOKEN')) {
            $this->session->delete($token);

            return true;
        }

        if (empty($data)) {
            $data = $this->post();
        }

        if (isset($data[$token]) && $this->session->get($token) === $data[$token]) {
            $this->session->delete($token);

            return true;
        }

        $this->session->delete($token);

        return false;
    }
}
