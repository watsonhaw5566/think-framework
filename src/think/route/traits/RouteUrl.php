<?php

declare(strict_types=1);

namespace think\route\traits;

use think\route\Url as UrlBuild;

trait RouteUrl
{
    public function buildUrl(string $url = '', array $vars = []): UrlBuild
    {
        return $this->app->make(UrlBuild::class, [$this, $this->app, $url, $vars], true);
    }
}
