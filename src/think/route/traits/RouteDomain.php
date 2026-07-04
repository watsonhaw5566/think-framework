<?php

declare(strict_types=1);

namespace think\route\traits;

use think\route\Domain;

trait RouteDomain
{
    public function domain(array|string $name, $rule = null): Domain
    {
        $domainName = is_array($name) ? array_shift($name) : $name;

        if (!isset($this->domains[$domainName])) {
            $domain = (new Domain($this, $domainName, $rule, $this->lazy))
                ->removeSlash($this->removeSlash)
                ->mergeRuleRegex($this->mergeRuleRegex);

            $this->domains[$domainName] = $domain;
        } else {
            $domain = $this->domains[$domainName];
            $domain->parseGroupRule($rule);
        }

        if (is_array($name) && !empty($name)) {
            foreach ($name as $item) {
                $this->domains[$item] = $domainName;
            }
        }

        return $domain;
    }

    public function getDomains(): array
    {
        return $this->domains;
    }

    public function getDomainBind(?string $domain = null)
    {
        if ($domain && isset($this->domains[$domain])) {
            $item = $this->domains[$domain];
            if (is_string($item)) {
                $item = $this->domains[$item];
            }

            return $item->getBind();
        }
    }

    protected function checkDomain(): Domain
    {
        $item = false;

        if (count($this->domains) > 1) {
            $subDomain = $this->request->subDomain();
            $domain    = $subDomain ? explode('.', $subDomain) : [];
            $domain2   = $domain ? array_pop($domain) : '';

            if ($domain) {
                $domain3 = array_pop($domain);
            }

            if (isset($this->domains[$this->host])) {
                $item = $this->domains[$this->host];
            } elseif (isset($this->domains[$subDomain])) {
                $item = $this->domains[$subDomain];
            } elseif (isset($this->domains['*.' . $domain2]) && !empty($domain3)) {
                $item      = $this->domains['*.' . $domain2];
                $panDomain = $domain3;
            } elseif (isset($this->domains['*']) && !empty($domain2)) {
                if ('www' != $domain2) {
                    $item      = $this->domains['*'];
                    $panDomain = $domain2;
                }
            }

            if (isset($panDomain)) {
                $this->request->setPanDomain($panDomain);
            }
        }

        if (false === $item) {
            $item = $this->domains['-'];
        }

        if (is_string($item)) {
            $item = $this->domains[$item];
        }

        return $item;
    }
}
