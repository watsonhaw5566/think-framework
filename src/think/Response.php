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

use InvalidArgumentException;
use Throwable;

/**
 * 响应输出基础类.
 */
abstract class Response
{
    /**
     * 原始数据.
     *
     * @var mixed
     */
    protected $data;

    /**
     * 当前contentType.
     *
     * @var string
     */
    protected $contentType = 'text/html';

    /**
     * 字符集.
     *
     * @var string
     */
    protected $charset = 'utf-8';

    /**
     * 状态码
     *
     * @var int
     */
    protected $code = 200;

    /**
     * 是否允许请求缓存.
     *
     * @var bool
     */
    protected $allowCache = true;

    /**
     * 输出参数.
     *
     * @var array
     */
    protected $options = [];

    /**
     * header参数.
     *
     * @var array
     */
    protected $header = [];

    /**
     * 输出内容.
     *
     * @var string
     */
    protected $content;

    /**
     * Cookie对象
     *
     * @var Cookie
     */
    protected $cookie;

    /**
     * Session对象
     *
     * @var Session
     */
    protected $session;

    /**
     * 初始化.
     *
     * @param mixed $data 输出数据
     * @param int   $code 状态码
     */
    protected function init($data = '', int $code = 200)
    {
        $this->data($data);
        $this->code = $code;

        $this->contentType($this->contentType, $this->charset);
    }

    /**
     * 创建Response对象
     *
     * @param mixed  $data 输出数据
     * @param string $type 输出类型
     * @param int    $code 状态码
     */
    public static function create($data = '', string $type = 'html', int $code = 200): Response
    {
        $class = str_contains($type, '\\') ? $type : '\think\response\\' . ucfirst(strtolower($type));

        return Container::getInstance()->invokeClass($class, [$data, $code]);
    }

    /**
     * 设置Session对象
     *
     * @param Session $session Session对象
     *
     * @return $this
     */
    public function setSession(Session $session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * 发送数据到客户端.
     *
     * @throws InvalidArgumentException
     */
    public function send(): void
    {
        try {
            // 处理输出数据
            $data = $this->getContent();
            if (!headers_sent()) {
                if (!empty($this->header)) {
                    // 发送状态码
                    http_response_code($this->code);
                    // 发送头部信息
                    foreach ($this->header as $name => $val) {
                        header($name . (!is_null($val) ? ':' . $val : ''));
                    }
                }

                if ($this->cookie) {
                    $this->cookie->save();
                }
            }

            $this->sendData($data);

            if (function_exists('fastcgi_finish_request')) {
                // 提高页面响应
                fastcgi_finish_request();
            }
        } catch (Throwable $e) {
            // 继续执行，不中断响应发送
            Container::getInstance()->log->error($e->getMessage());
        }
    }

    /**
     * 处理数据.
     *
     * @param mixed $data 要处理的数据
     *
     * @return mixed
     */
    protected function output($data)
    {
        return $data;
    }

    /**
     * 输出数据.
     *
     * @param string $data 要处理的数据
     */
    protected function sendData(string $data): void
    {
        echo $data;
    }

    /**
     * 输出的参数.
     *
     * @param mixed $options 输出参数
     *
     * @return $this
     */
    public function options(array $options = [])
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * 输出数据设置.
     *
     * @param mixed $data 输出数据
     *
     * @return $this
     */
    public function data($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * 是否允许请求缓存.
     *
     * @param bool $cache 允许请求缓存
     *
     * @return $this
     */
    public function allowCache(bool $cache)
    {
        $this->allowCache = $cache;

        return $this;
    }

    /**
     * 是否允许请求缓存.
     *
     * @return bool
     */
    public function isAllowCache()
    {
        return $this->allowCache;
    }

    /**
     * 设置Cookie.
     *
     * @param string $name   cookie名称
     * @param string $value  cookie值
     * @param mixed  $option 可选参数
     *
     * @return $this
     */
    public function cookie(string $name, string $value, $option = null)
    {
        $this->cookie->set($name, $value, $option);

        return $this;
    }

    /**
     * 设置响应头.
     *
     * @param array $header 参数
     *
     * @return $this
     */
    public function header(array $header = [])
    {
        $this->header = array_merge($this->header, $header);

        return $this;
    }

    /**
     * 设置页面输出内容.
     *
     * @param mixed $content
     *
     * @return $this
     */
    public function content($content)
    {
        if (
            null !== $content && !is_string($content) && !is_numeric($content) && !is_callable([
                $content,
                '__toString',
            ])
        ) {
            throw new InvalidArgumentException(sprintf('variable type error： %s', gettype($content)));
        }

        $this->content = (string) $content;

        return $this;
    }

    /**
     * 发送HTTP状态
     *
     * @param int $code 状态码
     *
     * @return $this
     */
    public function code(int $code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * LastModified.
     *
     * @return $this
     */
    public function lastModified(string $time)
    {
        $this->header['Last-Modified'] = $time;

        return $this;
    }

    /**
     * Expires.
     *
     * @return $this
     */
    public function expires(string $time)
    {
        $this->header['Expires'] = $time;

        return $this;
    }

    /**
     * ETag.
     *
     * @return $this
     */
    public function eTag(string $eTag)
    {
        $this->header['ETag'] = $eTag;

        return $this;
    }

    /**
     * 页面缓存控制.
     *
     * @param string $cache 状态码
     *
     * @return $this
     */
    public function cacheControl(string $cache)
    {
        $this->header['Cache-control'] = $cache;

        return $this;
    }

    /**
     * 页面输出类型.
     *
     * @param string $contentType 输出类型
     * @param string $charset     输出编码
     *
     * @return $this
     */
    public function contentType(string $contentType, string $charset = 'utf-8')
    {
        $this->header['Content-Type'] = $contentType . '; charset=' . $charset;

        return $this;
    }

    /**
     * 获取头部信息.
     *
     * @param string $name 头部名称
     *
     * @return mixed
     */
    public function getHeader(string $name = '')
    {
        if (!empty($name)) {
            return $this->header[$name] ?? null;
        }

        return $this->header;
    }

    /**
     * 获取原始数据.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /** 获取输出数据. */
    public function getContent(): string
    {
        if (null == $this->content) {
            $content = $this->output($this->data);

            if (
                null !== $content && !is_string($content) && !is_numeric($content) && !is_callable([
                    $content,
                    '__toString',
                ])
            ) {
                throw new InvalidArgumentException(sprintf('variable type error： %s', gettype($content)));
            }

            $this->content = (string) $content;
        }

        return $this->content;
    }

    /** 获取状态码 */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * 获取Cookie对象
     *
     * @return Cookie
     */
    public function getCookie()
    {
        return $this->cookie;
    }
}
