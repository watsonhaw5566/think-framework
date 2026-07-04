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
use think\request\InteractsWithInput;
use think\request\InteractsWithUrl;
use think\request\InteractsWithServer;
use think\request\InteractsWithSecurity;
use think\request\InteractsWithContent;
use think\request\InteractsWithRouting;
use think\request\InteractsWithData;
use think\route\Rule;

class Request implements ArrayAccess
{
    use InteractsWithInput;
    use InteractsWithUrl;
    use InteractsWithServer;
    use InteractsWithSecurity;
    use InteractsWithContent;
    use InteractsWithRouting;
    use InteractsWithData;

    /** @var array PathInfo 获取方式 */
    protected array $pathinfoFetch = ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL', 'PHP_SELF'];
    /** @var string PATHINFO变量名 */
    protected string $varPathinfo = 's';
    /** @var string 请求类型变量名 */
    protected string $varMethod = '_method';
    /** @var string AJAX变量名 */
    protected string $varAjax = '_ajax';
    /** @var string PJAX变量名 */
    protected string $varPjax = '_pjax';
    /** @var string 根域名 */
    protected string $rootDomain = '';
    /** @var array 域名特殊后缀 */
    protected array $domainSpecialSuffix = ['com', 'net', 'org', 'edu', 'gov', 'mil', 'co', 'info'];
    /** @var string HTTPS代理标识 */
    protected string $httpsAgentName = '';
    /** @var array 代理服务器IP列表 */
    protected array $proxyServerIp = [];
    /** @var array 代理服务器IP头 */
    protected array $proxyServerIpHeader = [];
    /** @var array MIME类型映射 */
    protected array $mimeType = [
        'xml'   => 'application/xml,text/xml,application/x-xml',
        'json'  => 'application/json,text/json,application/x-json',
        'js'    => 'application/javascript,application/x-javascript,text/javascript',
        'css'   => 'text/css',
        'html'  => 'text/html',
        'txt'   => 'text/plain',
        'image' => 'image/png,image/jpg,image/jpeg,image/gif,image/webp',
        'flash' => 'application/x-shockwave-flash',
        'pdf'   => 'application/pdf',
        'excel' => 'application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'word'  => 'application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    /** @var string 请求方法 */
    protected string $method = '';
    /** @var string 当前域名 */
    protected string $domain = '';
    /** @var string|null 子域名 */
    protected ?string $subDomain = null;
    /** @var string 泛域名 */
    protected string $panDomain = '';
    /** @var string 请求URL */
    protected string $url = '';
    /** @var string 基础URL */
    protected string $baseUrl = '';
    /** @var string 基础文件 */
    protected string $baseFile = '';
    /** @var string 网站根目录 */
    protected string $root = '';
    /** @var string|null PATHINFO */
    protected ?string $pathinfo = null;
    /** @var string 请求主机 */
    protected string $host = '';
    /** @var array GET参数 */
    protected array $get = [];
    /** @var array POST参数 */
    protected array $post = [];
    /** @var array PUT参数 */
    protected array $put = [];
    /** @var array DELETE参数 */
    protected array $delete = [];
    /** @var array PATCH参数 */
    protected array $patch = [];
    /** @var array REQUEST参数 */
    protected array $request = [];
    /** @var array COOKIE参数 */
    protected array $cookie = [];
    /** @var array SERVER参数 */
    protected array $server = [];
    /** @var array 请求头 */
    protected array $header = [];
    /** @var array 文件上传 */
    protected array $file = [];
    /** @var array 路由参数 */
    protected array $route = [];
    /** @var array 合并参数 */
    protected array $param = [];
    /** @var bool 是否合并参数 */
    protected bool $mergeParam = false;
    /** @var Env 环境变量 */
    protected Env $env;
    /** @var Session 会话 */
    protected Session $session;
    /** @var string|null 请求内容 */
    protected ?string $content = null;
    /** @var string 原始输入数据 */
    protected string $input = '';
    /** @var string 客户端真实IP */
    protected string $realIP = '';
    /** @var string|null 安全密钥 */
    protected ?string $secureKey = null;
    /** @var array 中间件数据 */
    protected array $middleware = [];
    /** @var Rule|null 路由规则 */
    protected ?Rule $rule = null;
    /** @var string 应用层 */
    protected string $layer = '';
    /** @var string 控制器名 */
    protected string $controller = '';
    /** @var string 操作名 */
    protected string $action = '';
    /** @var callable|array|string|null 全局过滤器 */
    protected $filter = null;

    public function __construct()
    {
    }

    public static function __make(App $app): Request
    {
        $request = new self();
        if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
            $header = $result;
        } else {
            $header = [];
            $server = $_SERVER;
            foreach ($server as $key => $val) {
                if (str_starts_with($key, 'HTTP_')) {
                    $key          = str_replace('_', '-', strtolower(substr($key, 5)));
                    $header[$key] = $val;
                }
            }
            if (isset($server['CONTENT_TYPE'])) {
                $header['content-type'] = $server['CONTENT_TYPE'];
            }
            if (isset($server['CONTENT_LENGTH'])) {
                $header['content-length'] = $server['CONTENT_LENGTH'];
            }
        }
        $request->header  = array_change_key_case($header);
        $request->server  = $_SERVER;
        $request->env     = $app->env;
        $inputData        = $request->getInputData($request->input);
        $request->get     = $_GET;
        $request->post    = $_POST ?: $inputData;
        $request->put     = $inputData;
        $request->request = $_REQUEST;
        $request->cookie  = $_COOKIE;
        $request->file    = $_FILES;

        return $request;
    }
}