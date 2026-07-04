<?php

declare(strict_types=1);

namespace think\route\traits;

use think\Container;
use Closure;

trait RuleConfig
{
    protected function setFullName(): void
    {
        if (str_contains($this->name, ':')) {
            $this->name = preg_replace(['/\[\:(\w+)\]/', '/\:(\w+)/'], ['<\1?>', '<\1>'], $this->name);
        }

        if ($this->parent && $this->parent->getFullName()) {
            $this->fullName = $this->parent->getFullName() . ($this->name ? '/' . $this->name : '');
            if ($this->sub) {
                $this->sub = $this->parent->getFullName() . '/' . $this->sub;
            }
        } else {
            $this->fullName = $this->name;
        }

        if ($this->name) {
            $this->router->getRuleName()->setGroup($this->name, $this);
        }
    }

    public function getDomain(): string
    {
        return $this->domain ?: '-';
    }

    public function getAlias(): string
    {
        return $this->alias ?: '';
    }

    protected function loadRoutes(string $dir): void
    {
        $routePath = root_path('route' . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR);
        if (is_dir($routePath)) {
            $files = glob($routePath . '*.php');
            foreach ($files as $file) {
                include_once $file;
            }

            $dirs = $this->config('route_auto_group') ? glob($routePath . '*', GLOB_ONLYDIR) : [];
            foreach ($dirs as $dir) {
                $groupName = str_replace('\\', '/', substr_replace($dir, '', 0, strlen($routePath)));
                if (!$this->router->getRuleName()->hasGroup($groupName)) {
                    $this->router->group($groupName);
                }
            }
        }
    }

    public function alias(string $alias)
    {
        $this->alias = $alias;
        $this->router->getRuleName()->setGroup($alias, $this);

        return $this;
    }

    public function parseGroupRule($rule): void
    {
        $origin = $this->router->getGroup();
        $this->router->setGroup($this);

        if ($rule instanceof Closure) {
            Container::getInstance()->invokeFunction($rule);
        } elseif ($this->sub) {
            $this->loadRoutes($this->sub);
        }

        $this->router->setGroup($origin);
        $this->hasParsed = true;
    }

    public function prefix(string $prefix)
    {
        if ($this->parent && $this->parent->getOption('prefix')) {
            $prefix = $this->parent->getOption('prefix') . $prefix;
        }

        return $this->setOption('prefix', $prefix);
    }

    public function mergeRuleRegex(bool $merge = true)
    {
        return $this->setOption('merge_rule_regex', $merge);
    }

    public function dispatcher(string $dispatch)
    {
        return $this->setOption('dispatcher', $dispatch);
    }

    public function getFullName(): string
    {
        return $this->fullName ?: '';
    }
}
