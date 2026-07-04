<?php

declare(strict_types=1);

namespace think\route\traits;

use think\Exception;
use think\Request;

trait RuleMatcher
{
    public function check(Request $request, string $url, bool $completeMatch = false)
    {
        if (!$this->checkOption($this->option, $request) || !$this->checkUrl($url)) {
            return false;
        }

        if (!$this->hasParsed) {
            $this->parseGroupRule($this->rule);
        }

        $method = strtolower($request->method());
        $rules  = $this->getRules($method);
        $option = $this->getOption();

        if (isset($option['complete_match'])) {
            $completeMatch = $option['complete_match'];
        }

        if (!empty($option['merge_rule_regex'])) {
            $result = $this->checkMergeRuleRegex($request, $rules, $url, $completeMatch);
            if (false !== $result) {
                return $result;
            }
        } else {
            foreach ($rules as $item) {
                $result = $item->check($request, $url, $completeMatch);
                if (false !== $result) {
                    return $result;
                }
            }
        }

        $miss = $this->getMissRule($method);
        if ($this->bind) {
            return $this->checkBind($request, $url, $option, $miss);
        }

        if ($miss) {
            return $miss->parseRule($request, '', $miss->getRoute(), $url, $miss->getOption());
        }

        return false;
    }

    protected function checkUrl(string $url): bool
    {
        $url = str_replace('|', '/', $url);
        if (!$this->config('url_route_must')) {
            $item = $this->router->getRuleName()->getName($url);
            if (!empty($item) && $item[0]['rule'] != $url) {
                return false;
            }
        }

        if ($this->fullName) {
            $pos = strpos($this->fullName, '<');

            if (false !== $pos) {
                $str = substr($this->fullName, 0, $pos - 1);
            } else {
                $str = $this->fullName;
            }

            if ($str && 0 !== stripos($url . '/', $str . '/')) {
                return false;
            }
        }

        return true;
    }

    protected function checkMergeRuleRegex(Request $request, array &$rules, string $url, bool $completeMatch)
    {
        $depr  = $this->config('pathinfo_depr');
        $url   = $depr . str_replace('|', $depr, $url);
        $regex = [];
        $items = [];

        foreach ($rules as $key => $item) {
            if ($item instanceof \think\route\RuleItem) {
                $rule = $depr . str_replace('/', $depr, $item->getRule());
                if ($depr == $rule && $depr != $url) {
                    unset($rules[$key]);
                    continue;
                }

                $complete = $item->getOption('complete_match', $completeMatch);

                if (!str_contains($rule, '<')) {
                    if (0 === strcasecmp($rule, $url) || (!$complete && 0 === strncasecmp($rule, $url, strlen($rule)))) {
                        return $item->checkRule($request, $url, []);
                    }

                    unset($rules[$key]);
                    continue;
                }

                $slash = preg_quote('/-' . $depr, '/');

                if ($matchRule = preg_split('/[' . $slash . ']<\w+\??>/', $rule, 2)) {
                    if ($matchRule[0] && 0 !== strncasecmp($rule, $url, strlen($matchRule[0]))) {
                        unset($rules[$key]);
                        continue;
                    }
                }

                if (preg_match_all('/[' . $slash . ']?<?\w+\??>?/', $rule, $matches)) {
                    unset($rules[$key]);
                    $pattern = array_merge($this->getPattern(), $item->getPattern());
                    $option  = array_merge($this->getOption(), $item->getOption());

                    $regex[$key] = $this->buildRuleRegex($rule, $matches[0], $pattern, $option, $complete, '_THINK_' . $key);
                    $items[$key] = $item;
                }
            } elseif ($item instanceof \think\route\RuleGroup) {
                $array = $item->getrules();

                return $this->checkMergeRuleRegex($request, $array, ltrim($url, $depr), $completeMatch);
            }
        }

        if (empty($regex)) {
            return false;
        }

        try {
            $result = preg_match('~^(?:' . implode('|', $regex) . ')~u', $url, $match);
        } catch (\Exception $e) {
            throw new Exception('route pattern error');
        }

        if ($result) {
            $var = [];
            $pos = null;
            foreach ($match as $key => $val) {
                if (is_string($key) && '' !== $val) {
                    [$name, $pos] = explode('_THINK_', $key);
                    $var[$name]   = $val;
                }
            }

            if (null === $pos) {
                foreach ($regex as $key => $item) {
                    if (str_starts_with(str_replace(['\/', '\-', '\\' . $depr], ['/', '-', $depr], $item), $match[0])) {
                        $pos = $key;
                        break;
                    }
                }
            }

            $rule  = $items[$pos]->getRule();
            $array = $this->router->getRule($rule);

            foreach ($array as $item) {
                if (in_array($item->getMethod(), ['*', strtolower($request->method())])) {
                    $result = $item->checkRule($request, $url, $var);
                    if (false !== $result) {
                        return $result;
                    }
                }
            }
        }

        return false;
    }
}
