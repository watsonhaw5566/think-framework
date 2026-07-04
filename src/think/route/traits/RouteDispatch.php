<?php

declare(strict_types=1);

namespace think\route\traits;

use Closure;
use think\Request;
use think\Response;
use think\exception\RouteNotFoundException;
use think\route\dispatch\Callback;

trait RouteDispatch
{
    public function dispatch(Request $request, bool|Closure $withRoute = true): Response
    {
        $this->request = $request;
        $this->host    = $this->request->host(true);
        $completeMatch = (bool) $this->config['route_complete_match'];
        $url           = str_replace($this->config['pathinfo_depr'], '|', $this->path());

        if ($withRoute) {
            if ($withRoute instanceof Closure) {
                $withRoute();
            }
            $dispatch = $this->check($url, $completeMatch);
        }

        if (empty($dispatch)) {
            $dispatch = $this->checkUrlDispatch($url);
        }

        $dispatch->init($this->app);

        return $this->app->middleware->pipeline('route')
            ->send($request)
            ->then(function () use ($dispatch) {
                return $dispatch->run();
            });
    }

    public function check(string $url, bool $completeMatch = false)
    {
        $result = $this->checkDomain()->check($this->request, $url, $completeMatch);

        if (false === $result && !empty($this->cross)) {
            $result = $this->cross->check($this->request, $url, $completeMatch);
        }

        if (false === $result && $this->config['url_route_must']) {
            throw new RouteNotFoundException();
        }

        return $result;
    }

    protected function checkUrlDispatch(string $url): \think\route\Dispatch
    {
        if ('OPTIONS' == $this->request->method()) {
            return new Callback($this->request, $this->group, function () {
                return Response::create('', 'html', 204)->header(['Allow' => 'GET, POST, PUT, DELETE']);
            });
        }

        return $this->group->auto()->checkBind($this->request, $url);
    }

    protected function path(): string
    {
        $suffix   = $this->config['url_html_suffix'];
        $pathinfo = $this->request->pathinfo();

        if (false === $suffix) {
            $path = $pathinfo;
        } elseif ($suffix) {
            $path = preg_replace('/\.(' . ltrim($suffix, '.') . ')$/i', '', $pathinfo);
        } else {
            $path = preg_replace('/\.' . $this->request->ext() . '$/i', '', $pathinfo);
        }

        return $path;
    }
}
