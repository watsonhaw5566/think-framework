<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace think\request;

use think\file\UploadedFile;
use think\File;
use think\facade\Lang;
use think\Exception;
use InvalidArgumentException;

/**
 * 输入/参数处理 Trait
 */
trait InteractsWithInput
{
    public function param(array|string $name = '', mixed $default = null, array|string|null $filter = ''): mixed
    {
        if (empty($this->mergeParam)) {
            $method = $this->method(true);
            $vars   = match ($method) {
                'POST'                   => $this->post(false),
                'PUT', 'DELETE', 'PATCH' => $this->put(false),
                default                  => [],
            };
            $this->param      = array_merge($this->param, $this->route(false), $this->get(false), $vars);
            $this->mergeParam = true;
        }
        if (is_array($name)) {
            return $this->only($name, $this->param, $filter);
        }

        return $this->input($this->param, $name, $default, $filter);
    }

    public function all(array|string $name = '', array|string|null $filter = ''): mixed
    {
        $data = array_merge($this->param(), $this->file() ?: []);
        if (is_array($name)) {
            $data = $this->only($name, $data, $filter);
        } elseif ($name) {
            $data = $data[$name] ?? null;
        }

        return $data;
    }

    public function route(array|bool|string $name = '', mixed $default = null, array|string|null $filter = ''): mixed
    {
        if (is_array($name)) {
            return $this->only($name, $this->route, $filter);
        }

        return $this->input($this->route, $name, $default, $filter);
    }

    public function get(array|bool|string $name = '', mixed $default = null, array|string|null $filter = ''): mixed
    {
        if (is_array($name)) {
            return $this->only($name, $this->get, $filter);
        }

        return $this->input($this->get, $name, $default, $filter);
    }

    public function middleware(?string $name = null, mixed $default = null): mixed
    {
        if (is_null($name)) {
            return $this->middleware;
        }

        return $this->middleware[$name] ?? $default;
    }

    public function post(array|bool|string $name = '', mixed $default = null, array|string|null $filter = ''): mixed
    {
        if (is_array($name)) {
            return $this->only($name, $this->post, $filter);
        }

        return $this->input($this->post, $name, $default, $filter);
    }

    public function put(array|bool|string $name = '', mixed $default = null, array|string|null $filter = ''): mixed
    {
        if (is_array($name)) {
            return $this->only($name, $this->put, $filter);
        }

        return $this->input($this->put, $name, $default, $filter);
    }

    public function delete(array|bool|string $name = '', mixed $default = null, array|string|null $filter = ''): mixed
    {
        return $this->put($name, $default, $filter);
    }

    public function patch(array|bool|string $name = '', mixed $default = null, array|string|null $filter = ''): mixed
    {
        return $this->put($name, $default, $filter);
    }

    public function request(array|bool|string $name = '', mixed $default = null, array|string|null $filter = ''): mixed
    {
        if (is_array($name)) {
            return $this->only($name, $this->request, $filter);
        }

        return $this->input($this->request, $name, $default, $filter);
    }

    public function env(string $name = '', ?string $default = null): mixed
    {
        if (empty($name)) {
            return $this->env->get();
        }

        return $this->env->get(strtoupper($name), $default);
    }

    public function session(string $name = '', mixed $default = null): mixed
    {
        if ('' === $name) {
            return $this->session->all();
        }

        return $this->session->get($name, $default);
    }

    public function cookie(string $name = '', mixed $default = null, array|string|null $filter = ''): mixed
    {
        if (!empty($name)) {
            $data = $this->getData($this->cookie, $name, $default);
        } else {
            $data = $this->cookie;
        }
        $filter = $this->getFilter($filter, $default);
        if (is_array($data)) {
            array_walk_recursive($data, [$this, 'filterValue'], $filter);
        } else {
            $this->filterValue($data, $name, $filter);
        }

        return $data;
    }

    public function server(string $name = '', string $default = ''): mixed
    {
        if (empty($name)) {
            return $this->server;
        }

        return $this->server[strtoupper($name)] ?? $default;
    }

    public function header(string $name = '', ?string $default = null): mixed
    {
        if ('' === $name) {
            return $this->header;
        }
        $name = str_replace('_', '-', strtolower($name));

        return $this->header[$name] ?? $default;
    }

    public function file(string $name = ''): mixed
    {
        $files = $this->file;
        if (!empty($files)) {
            if (str_contains($name, '.')) {
                [$name, $sub] = explode('.', $name);
            }
            $array = $this->dealUploadFile($files, $name);
            if ('' === $name) {
                return $array;
            }
            if (isset($sub, $array[$name][$sub])) {
                return $array[$name][$sub];
            }
            if (isset($array[$name])) {
                return $array[$name];
            }
        }

        return null;
    }

    protected function dealUploadFile(array $files, string $name): array
    {
        $array = [];
        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                $item  = [];
                $keys  = array_keys($file);
                $count = count($file['name']);
                for ($i = 0; $i < $count; ++$i) {
                    if ($file['error'][$i] > 0) {
                        if ($name == $key) {
                            $this->throwUploadFileError($file['error'][$i]);
                        } else {
                            continue;
                        }
                    }
                    $temp['key'] = $key;
                    foreach ($keys as $_key) {
                        $temp[$_key] = $file[$_key][$i];
                    }
                    $item[] = new UploadedFile($temp['tmp_name'], $temp['name'], $temp['type'], $temp['error']);
                }
                $array[$key] = $item;
            } else {
                if ($file instanceof File) {
                    $array[$key] = $file;
                } else {
                    if ($file['error'] > 0) {
                        if ($key == $name) {
                            $this->throwUploadFileError($file['error']);
                        } else {
                            continue;
                        }
                    }
                    $array[$key] = new UploadedFile($file['tmp_name'], $file['name'], $file['type'], $file['error']);
                }
            }
        }

        return $array;
    }

    protected function throwUploadFileError(int $error): never
    {
        static $fileUploadErrors = [
            1 => 'upload File size exceeds the maximum value',
            2 => 'upload File size exceeds the maximum value',
            3 => 'only the portion of file is uploaded',
            4 => 'no file to uploaded',
            6 => 'upload temp dir not found',
            7 => 'file write error',
        ];
        $msg = Lang::get($fileUploadErrors[$error]);

        throw new Exception($msg, $error);
    }

    protected function getInputData(string $content): array
    {
        $contentType = $this->contentType();
        if ('application/x-www-form-urlencoded' == $contentType) {
            parse_str($content, $data);

            return $data;
        }
        if (str_contains($contentType, 'json')) {
            return (array) json_decode($content, true);
        }

        return [];
    }

    public function input(array $data = [], bool|string $name = '', mixed $default = null, array|string|null $filter = ''): mixed
    {
        if (false === $name) {
            return $data;
        }
        $name = (string) $name;
        if ('' != $name) {
            if (str_contains($name, '/')) {
                [$name, $type] = explode('/', $name);
            }
            $data = $this->getData($data, $name);
        }

        return $this->filterData($data, $filter, $name, $default, $type ?? '');
    }

    protected function filterData(mixed $data, array|string|null $filter, string $name, mixed $default, string $type): mixed
    {
        if (is_null($data)) {
            return $default;
        }
        if (is_object($data)) {
            return $data;
        }
        $filter = $this->getFilter($filter, $default);
        if (is_array($data)) {
            array_walk_recursive($data, [$this, 'filterValue'], $filter);
        } else {
            $this->filterValue($data, $name, $filter);
        }
        if ($type) {
            $this->typeCast($data, $type);
        }

        return $data;
    }

    protected function typeCast(mixed &$data, string $type): void
    {
        $data = match (strtolower($type)) {
            'a'     => (array) $data,
            'b'     => (bool) $data,
            'd'     => (int) $data,
            'f'     => (float) $data,
            's'     => is_scalar($data) ? (string) $data : throw new InvalidArgumentException('variable type error：' . gettype($data)),
            default => $data,
        };
    }

    protected function getData(array $data, string $name, mixed $default = null): mixed
    {
        foreach (explode('.', $name) as $val) {
            if (isset($data[$val])) {
                $data = $data[$val];
            } else {
                return $default;
            }
        }

        return $data;
    }

    public function filter(callable|array|string|null $filter = null): mixed
    {
        if (is_null($filter)) {
            return $this->filter;
        }
        $this->filter = $filter;

        return $this;
    }

    protected function getFilter(array|string|null $filter, mixed $default): array
    {
        if (is_null($filter)) {
            $filter = [];
        } else {
            $filter = $filter ?: $this->filter;
            if (is_string($filter) && !str_contains($filter, '/')) {
                $filter = explode(',', $filter);
            } else {
                $filter = (array) $filter;
            }
        }
        $filter[] = $default;

        return $filter;
    }

    public function filterValue(mixed &$value, mixed $key, array $filters): mixed
    {
        $default = array_pop($filters);
        foreach ($filters as $filter) {
            if (is_callable($filter)) {
                if (is_null($value)) {
                    continue;
                }
                $value = call_user_func($filter, $value);
            } elseif (is_scalar($value)) {
                if (is_string($filter) && str_contains($filter, '/')) {
                    if (!preg_match($filter, $value)) {
                        $value = $default;
                        break;
                    }
                } elseif (!empty($filter)) {
                    $value = filter_var($value, is_int($filter) ? $filter : filter_id($filter));
                    if (false === $value) {
                        $value = $default;
                        break;
                    }
                }
            }
        }

        return $value;
    }

    public function has(string $name, string $type = 'param', bool $checkEmpty = false): bool
    {
        if (!in_array($type, ['param', 'get', 'post', 'put', 'patch', 'route', 'delete', 'cookie', 'session', 'env', 'request', 'server', 'header', 'file'])) {
            return false;
        }
        $param = empty($this->{$type}) ? $this->{$type}() : $this->{$type};
        if (is_object($param)) {
            return $param->has($name);
        }
        foreach (explode('.', $name) as $val) {
            if (isset($param[$val])) {
                $param = $param[$val];
            } else {
                return false;
            }
        }

        return !(($checkEmpty && '' === $param));
    }

    public function only(array $name, array|string $data = 'param', array|string|null $filter = ''): array
    {
        $data = is_array($data) ? $data : $this->{$data}();
        $item = [];
        foreach ($name as $key => $val) {
            $type = '';
            if (is_int($key)) {
                if (str_contains($val, '/')) {
                    [$val, $type] = explode('/', $val);
                }
                $default = null;
                $key     = $val;
                if (!key_exists($key, $data)) {
                    continue;
                }
            } else {
                if (str_contains($key, '/')) {
                    [$key, $type] = explode('/', $key);
                }
                $default = $val;
            }
            $item[$key] = $this->filterData($data[$key] ?? $default, $filter, $key, $default, $type);
        }

        return $item;
    }

    public function except(array $name, string $type = 'param'): array
    {
        $param = $this->{$type}();
        foreach ($name as $key) {
            if (isset($param[$key])) {
                unset($param[$key]);
            }
        }

        return $param;
    }
}
