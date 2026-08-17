<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2025 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare(strict_types=1);

namespace think;

use ArrayAccess;

/**
 * Env管理类
 * @package think
 */
class Env implements ArrayAccess
{
    /**
     * 环境变量数据
     * @var array
     */
    protected $data = [];

    /**
     * 数据转换映射
     * @var array
     */
    protected $convert = [
        'true'  => true,
        'false' => false,
        'off'   => false,
        'on'    => true,
    ];

    public function __construct()
    {
        $this->data = $_ENV;
    }

    /**
     * 读取环境变量定义文件
     * @access public
     * @param string $file 环境变量定义文件
     * @return void
     */
    public function load(string $file): void
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION)) ?: 'env';
        $env       = $this->parseFile($file, $extension);
        $this->set($env);
    }

    /**
     * 根据扩展名解析环境变量文件
     * @access protected
     * @param string $file      文件路径
     * @param string $extension 扩展名
     * @return array
     */
    protected function parseFile(string $file, string $extension): array
    {
        return match ($extension) {
            'env', 'ini'  => $this->parseIni($file),
            'yml', 'yaml' => $this->parseYaml($file),
            default       => throw new Exception("不支持的环境变量文件格式: {$extension}"),
        };
    }

    /**
     * 解析 INI 格式文件
     * @access protected
     * @param string $file 文件路径
     * @return array
     */
    protected function parseIni(string $file): array
    {
        return parse_ini_file($file, true, INI_SCANNER_RAW) ?: [];
    }

    /**
     * 解析 YAML 格式文件
     * @access protected
     * @param string $file 文件路径
     * @return array
     */
    protected function parseYaml(string $file): array
    {
        if (!class_exists(\Symfony\Component\Yaml\Yaml::class)) {
            throw new Exception('使用 YAML 格式环境变量文件需先安装 symfony/yaml: composer require symfony/yaml');
        }

        $env = \Symfony\Component\Yaml\Yaml::parseFile($file);

        return is_array($env) ? $env : [];
    }

    /**
     * 获取环境变量值
     * @access public
     * @param string $name    环境变量名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function get(?string $name = null, $default = null)
    {
        if (is_null($name)) {
            return $this->data;
        }

        $name = strtoupper(str_replace('.', '_', $name));
        if (isset($this->data[$name])) {
            $result = $this->data[$name];

            if (is_string($result) && isset($this->convert[$result])) {
                return $this->convert[$result];
            }

            return $result;
        }

        return $this->getEnv($name, $default);
    }

    protected function getEnv(string $name, $default = null)
    {
        $result = getenv('PHP_' . $name);

        if (false === $result) {
            return $default;
        }

        if (isset($this->convert[$result])) {
            $result = $this->convert[$result];
        }

        if (!isset($this->data[$name])) {
            $this->data[$name] = $result;
        }

        return $result;
    }

    /**
     * 设置环境变量值
     * @access public
     * @param string|array $env   环境变量
     * @param mixed        $value 值
     * @return void
     */
    public function set($env, $value = null): void
    {
        if (is_array($env)) {
            $env = array_change_key_case($env, CASE_UPPER);

            foreach ($this->flattenArray($env) as $key => $val) {
                $this->data[$key] = $val;
            }
        } else {
            $name = strtoupper(str_replace('.', '_', $env));

            $this->data[$name] = $value;
        }
    }

    /**
     * 递归将多维关联数组扁平化为一维（使用 _ 拼接键名）
     * 索引数组（数字键）保持原样，不参与扁平化
     * @access protected
     * @param array  $array 待处理数组
     * @param string $prefix 键名前缀
     * @return array
     */
    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? strtoupper((string) $key) : $prefix . '_' . strtoupper((string) $key);

            if (is_array($value) && $this->isAssocArray($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * 判断数组是否为关联数组（非纯数字连续索引）
     * @access protected
     * @param array $array
     * @return bool
     */
    protected function isAssocArray(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * 检测是否存在环境变量
     * @access public
     * @param string $name 参数名
     * @return bool
     */
    public function has(string $name): bool
    {
        return !is_null($this->get($name));
    }

    /**
     * 设置环境变量
     * @access public
     * @param string $name  参数名
     * @param mixed  $value 值
     */
    public function __set(string $name, $value): void
    {
        $this->set($name, $value);
    }

    /**
     * 获取环境变量
     * @access public
     * @param string $name 参数名
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }

    /**
     * 检测是否存在环境变量
     * @access public
     * @param string $name 参数名
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    // ArrayAccess
    public function offsetSet(mixed $name, mixed $value): void
    {
        $this->set($name, $value);
    }

    public function offsetExists(mixed $name): bool
    {
        return $this->__isset($name);
    }

    public function offsetUnset(mixed $name): void
    {
        throw new Exception('not support: unset');
    }

    public function offsetGet(mixed $name): mixed
    {
        return $this->get($name);
    }
}
