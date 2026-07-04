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

class Request implements ArrayAccess
{
    use InteractsWithInput;
    use InteractsWithUrl;
    use InteractsWithServer;
    use InteractsWithSecurity;
    use InteractsWithContent;
    use InteractsWithRouting;
    use InteractsWithData;

    protected $pathinfoFetch       = ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL', 'PHP_SELF'];
    protected $varPathinfo         = 's';
    protected $varMethod           = '_method';
    protected $varAjax             = '_ajax';
    protected $varPjax             = '_pjax';
    protected $rootDomain          = '';
    protected $domainSpecialSuffix = ['com', 'net', 'org', 'edu', 'gov', 'mil', 'co', 'info'];
    protected $httpsAgentName      = '';
    protected $proxyServerIp       = [];
    protected $proxyServerIpHeader = [];
    protected $mimeType            = [
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
    protected $method     = '';
    protected $domain     = '';
    protected $subDomain  = null;
    protected $panDomain  = '';
    protected $url        = '';
    protected $baseUrl    = '';
    protected $baseFile   = '';
    protected $root       = '';
    protected $pathinfo   = null;
    protected $host       = '';
    protected $get        = [];
    protected $post       = [];
    protected $put        = [];
    protected $delete     = [];
    protected $patch      = [];
    protected $request    = [];
    protected $cookie     = [];
    protected $server     = [];
    protected $header     = [];
    protected $file       = [];
    protected $route      = [];
    protected $param      = [];
    protected $mergeParam = false;
    protected $env;
    protected $session;
    protected $content;
    protected $input      = '';
    protected $realIP     = '';
    protected $secureKey  = null;
    protected $middleware = [];
    protected $rule;
    protected $layer      = '';
    protected $controller = '';
    protected $action     = '';
    protected $filter;

    public function __construct()
    {
    }

    public static function __make(App $app)
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