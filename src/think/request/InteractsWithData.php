<?php

declare (strict_types=1);

namespace think\request;

use think\Env;
use think\Session;

trait InteractsWithData
{
    public function withMiddleware(array $middleware)
    {
        $this->middleware = array_merge($this->middleware, $middleware);

        return $this;
    }

    public function withGet(array $get)
    {
        $this->get = $get;

        return $this;
    }

    public function withPost(array $post)
    {
        $this->post = $post;

        return $this;
    }

    public function withCookie(array $cookie)
    {
        $this->cookie = $cookie;

        return $this;
    }

    public function setCookie(string $name, mixed $value)
    {
        $this->cookie[$name] = $value;
    }

    public function withSession(Session $session)
    {
        $this->session = $session;

        return $this;
    }

    public function withServer(array $server)
    {
        $this->server = array_change_key_case($server, CASE_UPPER);

        return $this;
    }

    public function withHeader(array $header)
    {
        $this->header = array_change_key_case($header);

        return $this;
    }

    public function withEnv(Env $env)
    {
        $this->env = $env;

        return $this;
    }

    public function withInput(string $input)
    {
        $this->input = $input;
        if (!empty($input)) {
            $inputData = $this->getInputData($input);
            if (!empty($inputData)) {
                $this->post = $inputData;
                $this->put  = $inputData;
            }
        }

        return $this;
    }

    public function withFiles(array $files)
    {
        $this->file = $files;

        return $this;
    }

    public function withRoute(array $route)
    {
        $this->route = $route;

        return $this;
    }

    public function __set(string $name, $value)
    {
        $this->middleware[$name] = $value;
    }

    public function __get(string $name)
    {
        return $this->middleware($name);
    }

    public function __isset(string $name): bool
    {
        return isset($this->middleware[$name]);
    }

    public function offsetExists(mixed $name): bool
    {
        return $this->has($name);
    }

    public function offsetGet(mixed $name): mixed
    {
        return $this->param($name);
    }

    public function offsetSet(mixed $name, mixed $value): void
    {
    }

    public function offsetUnset(mixed $name): void
    {
    }
}
