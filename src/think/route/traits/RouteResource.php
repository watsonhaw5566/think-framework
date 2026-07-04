<?php

declare(strict_types=1);

namespace think\route\traits;

use Closure;
use think\route\Resource;
use think\route\ResourceRegister;

trait RouteResource
{
    protected $rest = [
        'index'  => ['get', '', 'index'],
        'create' => ['get', '/create', 'create'],
        'edit'   => ['get', '/<id>/edit', 'edit'],
        'read'   => ['get', '/<id>', 'read'],
        'save'   => ['post', '', 'save'],
        'update' => ['put', '/<id>', 'update'],
        'delete' => ['delete', '/<id>', 'delete'],
    ];

    public function resource(string $rule, string $route, ?Closure $extend = null)
    {
        $resource = (new Resource($this, $this->group, $rule, $route, $this->rest))->extend($extend);

        if (!$this->lazy) {
            return new ResourceRegister($resource);
        }

        return $resource;
    }

    public function rest(array|string $name, array|bool $resource = [])
    {
        if (is_array($name)) {
            $this->rest = $resource ? $name : array_merge($this->rest, $name);
        } else {
            $this->rest[$name] = $resource;
        }

        return $this;
    }

    public function getRest(?string $name = null)
    {
        if (is_null($name)) {
            return $this->rest;
        }

        return $this->rest[$name] ?? null;
    }
}
