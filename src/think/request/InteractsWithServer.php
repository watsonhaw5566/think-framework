<?php

declare (strict_types=1);

namespace think\request;

trait InteractsWithServer
{
    public function setMethod(string $method)
    {
        $this->method = strtoupper($method);

        return $this;
    }

    public function method(bool $origin = false): string
    {
        if ($origin) {
            return $this->server('REQUEST_METHOD') ?: 'GET';
        }

        if (!$this->method) {
            if (isset($this->post[$this->varMethod])) {
                $method = strtolower($this->post[$this->varMethod]);
                if (in_array($method, ['get', 'post', 'put', 'patch', 'delete'])) {
                    $this->method    = strtoupper($method);
                    $this->{$method} = $this->post;
                } else {
                    $this->method = 'POST';
                }
                unset($this->post[$this->varMethod]);
            } elseif ($this->server('HTTP_X_HTTP_METHOD_OVERRIDE')) {
                $this->method = strtoupper($this->server('HTTP_X_HTTP_METHOD_OVERRIDE'));
            } else {
                $this->method = $this->server('REQUEST_METHOD') ?: 'GET';
            }
        }

        return $this->method;
    }

    public function isGet(): bool
    {
        return 'GET' == $this->method();
    }

    public function isPost(): bool
    {
        return 'POST' == $this->method();
    }

    public function isPut(): bool
    {
        return 'PUT' == $this->method();
    }

    public function isDelete(): bool
    {
        return 'DELETE' == $this->method();
    }

    public function isHead(): bool
    {
        return 'HEAD' == $this->method();
    }

    public function isPatch(): bool
    {
        return 'PATCH' == $this->method();
    }

    public function isOptions(): bool
    {
        return 'OPTIONS' == $this->method();
    }

    public function isCli(): bool
    {
        return PHP_SAPI == 'cli';
    }

    public function isCgi(): bool
    {
        return str_starts_with(PHP_SAPI, 'cgi');
    }

    public function isSsl(): bool
    {
        if ($this->server('HTTPS') && ('1' == $this->server('HTTPS') || 'on' == strtolower($this->server('HTTPS')))) {
            return true;
        }
        if ('https' == $this->server('REQUEST_SCHEME')) {
            return true;
        }
        if ('443' == $this->server('SERVER_PORT')) {
            return true;
        }
        if ('https' == $this->server('HTTP_X_FORWARDED_PROTO')) {
            return true;
        }
        if ($this->httpsAgentName && $this->server($this->httpsAgentName)) {
            return true;
        }

        return false;
    }

    public function isJson(): bool
    {
        $acceptType = $this->type();

        return str_contains($acceptType, 'json');
    }

    public function isAjax(bool $ajax = false): bool
    {
        $value  = $this->server('HTTP_X_REQUESTED_WITH');
        $result = $value && 'xmlhttprequest' == strtolower($value) ? true : false;

        if (true === $ajax) {
            return $result;
        }

        return $this->param($this->varAjax) ? true : $result;
    }

    public function isPjax(bool $pjax = false): bool
    {
        $result = !empty($this->server('HTTP_X_PJAX')) ? true : false;

        if (true === $pjax) {
            return $result;
        }

        return $this->param($this->varPjax) ? true : $result;
    }

    public function ip(): string
    {
        if (!empty($this->realIP)) {
            return $this->realIP;
        }

        $this->realIP = $this->server('REMOTE_ADDR', '');

        $proxyIp       = $this->proxyServerIp;
        $proxyIpHeader = $this->proxyServerIpHeader;

        if (count($proxyIp) > 0 && count($proxyIpHeader) > 0) {
            foreach ($proxyIpHeader as $header) {
                $tempIP = $this->server($header);

                if (empty($tempIP)) {
                    continue;
                }

                $tempIP = trim(explode(',', $tempIP)[0]);

                if (!$this->isValidIP($tempIP)) {
                    $tempIP = null;
                } else {
                    break;
                }
            }

            if (!empty($tempIP)) {
                $realIPBin = $this->ip2bin($this->realIP);

                foreach ($proxyIp as $ip) {
                    $serverIPElements = explode('/', $ip);
                    $serverIP         = $serverIPElements[0];
                    $serverIPPrefix   = $serverIPElements[1] ?? 128;
                    $serverIPBin      = $this->ip2bin($serverIP);

                    if (strlen($realIPBin) !== strlen($serverIPBin)) {
                        continue;
                    }

                    if (0 === strncmp($realIPBin, $serverIPBin, (int) $serverIPPrefix)) {
                        $this->realIP = $tempIP;
                        break;
                    }
                }
            }
        }

        if (!$this->isValidIP($this->realIP)) {
            $this->realIP = '0.0.0.0';
        }

        return $this->realIP;
    }

    public function isValidIP(string $ip, string $type = ''): bool
    {
        $flag = match (strtolower($type)) {
            'ipv4'  => FILTER_FLAG_IPV4,
            'ipv6'  => FILTER_FLAG_IPV6,
            default => 0,
        };

        return boolval(filter_var($ip, FILTER_VALIDATE_IP, $flag));
    }

    public function ip2bin(string $ip): string
    {
        if ($this->isValidIP($ip, 'ipv6')) {
            $IPHex = str_split(bin2hex(inet_pton($ip)), 4);
            foreach ($IPHex as $key => $value) {
                $IPHex[$key] = intval($value, 16);
            }
            $IPBin = vsprintf('%016b%016b%016b%016b%016b%016b%016b%016b', $IPHex);
        } else {
            $IPHex = str_split(bin2hex(inet_pton($ip)), 2);
            foreach ($IPHex as $key => $value) {
                $IPHex[$key] = intval($value, 16);
            }
            $IPBin = vsprintf('%08b%08b%08b%08b', $IPHex);
        }

        return $IPBin;
    }

    public function isMobile(): bool
    {
        if ($this->server('HTTP_VIA') && stristr($this->server('HTTP_VIA'), 'wap')) {
            return true;
        }
        if ($this->server('HTTP_ACCEPT') && str_contains(strtoupper($this->server('HTTP_ACCEPT')), 'VND.WAP.WML')) {
            return true;
        }
        if ($this->server('HTTP_X_WAP_PROFILE') || $this->server('HTTP_PROFILE')) {
            return true;
        }
        if ($this->server('HTTP_USER_AGENT') && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $this->server('HTTP_USER_AGENT'))) {
            return true;
        }

        return false;
    }

    public function type(): string
    {
        $accept = $this->server('HTTP_ACCEPT');

        if (empty($accept)) {
            return '';
        }

        foreach ($this->mimeType as $key => $val) {
            $array = explode(',', $val);
            foreach ($array as $k => $v) {
                if (stristr($accept, $v)) {
                    return $key;
                }
            }
        }

        return '';
    }

    public function mimeType($type, $val = ''): void
    {
        if (is_array($type)) {
            $this->mimeType = array_merge($this->mimeType, $type);
        } else {
            $this->mimeType[$type] = $val;
        }
    }
}
