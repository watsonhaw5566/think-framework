<?php

declare (strict_types=1);

namespace think\request;

use think\Env;
use think\Session;

trait InteractsWithData
{
    public function withMiddleware(array $middleware): static
    {
        $this->middleware = array_merge($this->middleware, $middleware);

        return $this;
    }

    public function withGet(array $get): static
    {
        $this->get = $get;

        return $this;
    }

    public function withPost(array $post): static
    {
        $this->post = $post;

        return $this;
    }

    public function withCookie(array $cookie): static
    {
        $this->cookie = $cookie;

        return $this;
    }

    public function setCookie(string $name, mixed $value): void
    {
        $this->cookie[$name] = $value;
    }

    public function withSession(Session $session): static
    {
        $this->session = $session;

        return $this;
    }

    public function withServer(array $server): static
    {
        $this->server = array_change_key_case($server, CASE_UPPER);

        return $this;
    }

    public function withHeader(array $header): static
    {
        $this->header = array_change_key_case($header);

        return $this;
    }

    public function withEnv(Env $env): static
    {
        $this->env = $env;

        return $this;
    }

    public function withInput(string $input): static
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

    public function withFiles(array $files): static
    {
        $this->file = $files;

        return $this;
    }

    public function withRoute(array $route): static
    {
        $this->route = $route;

        return $this;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->middleware[$name] = $value;
    }

    public function __get(string $name): mixed
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
