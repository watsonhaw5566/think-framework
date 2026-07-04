<?php

declare (strict_types=1);

namespace think\request;

trait InteractsWithUrl
{
    public function setDomain(string $domain): static
    {
        $this->domain = $domain;

        return $this;
    }

    public function domain(bool $port = false): string
    {
        return $this->scheme() . '://' . $this->host($port);
    }

    public function setRootDomain(string $domain): static
    {
        $this->rootDomain = $domain;

        return $this;
    }

    public function rootDomain(): string
    {
        $root = $this->rootDomain;
        if (!$root) {
            $item  = explode('.', $this->host(true));
            $count = count($item);
            if ($count > 1) {
                $root = $item[$count - 2] . '.' . $item[$count - 1];
                if ($count > 2 && in_array($item[$count - 2], $this->domainSpecialSuffix)) {
                    $root = $item[$count - 3] . '.' . $root;
                }
            } else {
                $root = $item[0];
            }
        }

        return $root;
    }

    public function setSubDomain(string $domain): static
    {
        $this->subDomain = $domain;

        return $this;
    }

    public function subDomain(): string
    {
        if (is_null($this->subDomain)) {
            $rootDomain = $this->rootDomain();
            if ($rootDomain) {
                $sub             = stristr($this->host(), $rootDomain, true);
                $this->subDomain = $sub ? rtrim($sub, '.') : '';
            } else {
                $this->subDomain = '';
            }
        }

        return $this->subDomain;
    }

    public function setPanDomain(string $domain): static
    {
        $this->panDomain = $domain;

        return $this;
    }

    public function panDomain(): string
    {
        return $this->panDomain ?: '';
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function url(bool $complete = false): string
    {
        if ($this->url) {
            $url = $this->url;
        } elseif ($this->server('HTTP_X_REWRITE_URL')) {
            $url = $this->server('HTTP_X_REWRITE_URL');
        } elseif ($this->server('REQUEST_URI')) {
            $url = $this->server('REQUEST_URI');
        } elseif ($this->server('ORIG_PATH_INFO')) {
            $url = $this->server('ORIG_PATH_INFO') . (!empty($this->server('QUERY_STRING')) ? '?' . $this->server('QUERY_STRING') : '');
        } elseif (isset($_SERVER['argv'][1])) {
            $url = $_SERVER['argv'][1];
        } else {
            $url = '';
        }

        return $complete ? $this->domain() . $url : $url;
    }

    public function setBaseUrl(string $url): static
    {
        $this->baseUrl = $url;

        return $this;
    }

    public function baseUrl(bool $complete = false): string
    {
        if (!$this->baseUrl) {
            $str           = $this->url();
            $this->baseUrl = str_contains($str, '?') ? strstr($str, '?', true) : $str;
        }

        return $complete ? $this->domain() . $this->baseUrl : $this->baseUrl;
    }

    public function baseFile(bool $complete = false): string
    {
        if (!$this->baseFile) {
            $url = '';
            if (!$this->isCli()) {
                $script_name = basename($this->server('SCRIPT_FILENAME'));
                if (basename($this->server('SCRIPT_NAME')) === $script_name) {
                    $url = $this->server('SCRIPT_NAME');
                } elseif (basename($this->server('PHP_SELF')) === $script_name) {
                    $url = $this->server('PHP_SELF');
                } elseif (basename($this->server('ORIG_SCRIPT_NAME')) === $script_name) {
                    $url = $this->server('ORIG_SCRIPT_NAME');
                } elseif (($pos = strpos($this->server('PHP_SELF'), '/' . $script_name)) !== false) {
                    $url = substr($this->server('SCRIPT_NAME'), 0, $pos) . '/' . $script_name;
                } elseif ($this->server('DOCUMENT_ROOT') && str_starts_with($this->server('SCRIPT_FILENAME'), $this->server('DOCUMENT_ROOT'))) {
                    $url = str_replace('\\', '/', str_replace($this->server('DOCUMENT_ROOT'), '', $this->server('SCRIPT_FILENAME')));
                }
            }
            $this->baseFile = $url;
        }

        return $complete ? $this->domain() . $this->baseFile : $this->baseFile;
    }

    public function setRoot(string $url): static
    {
        $this->root = $url;

        return $this;
    }

    public function root(bool $complete = false): string
    {
        if (!$this->root) {
            $file = $this->baseFile();
            if ($file && !str_starts_with($this->url(), $file)) {
                $file = str_replace('\\', '/', dirname($file));
            }
            $this->root = rtrim($file, '/');
        }

        return $complete ? $this->domain() . $this->root : $this->root;
    }

    public function rootUrl(): string
    {
        $base = $this->root();
        $root = str_contains($base, '.') ? ltrim(dirname($base), DIRECTORY_SEPARATOR) : $base;
        if ('' != $root) {
            $root = '/' . ltrim($root, '/');
        }

        return $root;
    }

    public function setPathinfo(string $pathinfo): static
    {
        $this->pathinfo = $pathinfo;

        return $this;
    }

    public function pathinfo(): string
    {
        if (is_null($this->pathinfo)) {
            if (isset($_GET[$this->varPathinfo])) {
                $pathinfo = $_GET[$this->varPathinfo];
                unset($_GET[$this->varPathinfo], $this->get[$this->varPathinfo]);
            } elseif ($this->server('PATH_INFO')) {
                $pathinfo = $this->server('PATH_INFO');
            } elseif (str_contains(PHP_SAPI, 'cli')) {
                $pathinfo = str_contains($this->server('REQUEST_URI'), '?') ? strstr($this->server('REQUEST_URI'), '?', true) : $this->server('REQUEST_URI');
            }
            if (!isset($pathinfo)) {
                foreach ($this->pathinfoFetch as $type) {
                    if ($this->server($type)) {
                        $pathinfo = str_starts_with($this->server($type), $this->server('SCRIPT_NAME')) ? substr($this->server($type), strlen($this->server('SCRIPT_NAME'))) : $this->server($type);
                        break;
                    }
                }
            }
            if (!empty($pathinfo)) {
                unset($this->get[$pathinfo], $this->request[$pathinfo]);
            }
            $this->pathinfo = empty($pathinfo) || '/' == $pathinfo ? '' : ltrim($pathinfo, '/');
        }

        return $this->pathinfo;
    }

    public function ext(): string
    {
        return pathinfo($this->pathinfo(), PATHINFO_EXTENSION);
    }

    public function time(bool $float = false): int|float
    {
        return $float ? $this->server('REQUEST_TIME_FLOAT') : $this->server('REQUEST_TIME');
    }

    public function scheme(): string
    {
        return $this->isSsl() ? 'https' : 'http';
    }

    public function query(): string
    {
        return $this->server('QUERY_STRING', '');
    }

    public function setHost(string $host): static
    {
        $this->host = $host;

        return $this;
    }

    public function host(bool $strict = false): string
    {
        if ($this->host) {
            $host = $this->host;
        } else {
            $host = strval($this->server('HTTP_X_FORWARDED_HOST') ?: $this->server('HTTP_HOST'));
        }

        return true === $strict && str_contains($host, ':') ? strstr($host, ':', true) : $host;
    }

    public function port(): int
    {
        return (int) ($this->server('HTTP_X_FORWARDED_PORT') ?: $this->server('SERVER_PORT', ''));
    }

    public function protocol(): string
    {
        return $this->server('SERVER_PROTOCOL', '');
    }

    public function remotePort(): int
    {
        return (int) $this->server('REMOTE_PORT', '');
    }

    public function contentType(): string
    {
        $contentType = $this->header('Content-Type');
        if ($contentType) {
            if (str_contains($contentType, ';')) {
                [$type] = explode(';', $contentType);
            } else {
                $type = $contentType;
            }

            return trim($type);
        }

        return '';
    }

    public function secureKey(): string
    {
        if (is_null($this->secureKey)) {
            $this->secureKey = uniqid('', true);
        }

        return $this->secureKey;
    }
}
