# ThinkPHP Route 组件

路由组件负责 URL 请求的解析和分发，将 HTTP 请求映射到对应的控制器和方法。

## 目录结构

```
route/
├── traits/           # Trait 实现
│   ├── RouteRegister.php   # 路由注册 (Route 类)
│   ├── RouteGroup.php      # 路由分组 (Route 类)
│   ├── RouteResource.php   # 资源路由 (Route 类)
│   ├── RouteDomain.php     # 域名路由 (Route 类)
│   ├── RouteDispatch.php   # 路由分发 (Route 类)
│   ├── RouteUrl.php        # URL 生成 (Route 类)
│   ├── RuleManager.php     # 规则管理 (RuleGroup 类)
│   ├── RuleMatcher.php     # 规则匹配 (RuleGroup 类)
│   ├── RuleBinder.php      # 路由绑定 (RuleGroup 类)
│   └── RuleConfig.php      # 路由配置 (RuleGroup 类)
├── dispatch/         # 调度器
│   ├── Callback.php
│   ├── Controller.php
│   ├── Redirect.php
│   └── View.php
├── Domain.php        # 域名管理
├── Resource.php      # 资源路由
├── Rule.php          # 路由规则
├── RuleGroup.php     # 路由分组
├── RuleItem.php      # 路由项
├── RuleName.php      # 路由命名
├── UrlBuild.php      # URL 构建
└── Url.php           # URL 类
```

## 基本用法

### 注册路由

```php
use think\Route;

$route = new Route($app);

// 注册 GET 请求路由
$route->get('user/:id', 'User/read');

// 注册 POST 请求路由
$route->post('user', 'User/create');

// 注册任意请求方法路由
$route->any('hello', 'Index/hello');

// 注册多个请求方法路由
$route->rule('user/:id', 'User/update', 'PUT|PATCH');
```

### 路由分组

```php
// 基础分组
$route->group('api', function () use ($route) {
    $route->get('users', 'User/index');
    $route->get('user/:id', 'User/read');
});

// 嵌套分组
$route->group('api', function () use ($route) {
    $route->group('v1', function () use ($route) {
        $route->get('items', 'Item/index');
    });
});

// 设置分组参数
$route->group('api', function () use ($route) {
    $route->get('users', 'User/index');
})->pattern(['id' => '\d+'])->ext('html');
```

### 资源路由

```php
// 注册资源路由
$route->resource('photos', 'Photo');

// 生成的路由：
// GET     /photos          -> Photo/index
// GET     /photos/create   -> Photo/create
// POST    /photos          -> Photo/save
// GET     /photos/:id      -> Photo/read
// GET     /photos/:id/edit -> Photo/edit
// PUT     /photos/:id      -> Photo/update
// DELETE  /photos/:id      -> Photo/delete

// 自定义资源路由
$route->resource('photos', 'Photo')->only(['index', 'read']);
$route->resource('photos', 'Photo')->except(['delete']);
```

### 域名路由

```php
// 绑定域名到路由
$route->domain('api.example.com', function () use ($route) {
    $route->get('users', 'Api/User/index');
});

// 绑定多个域名
$route->domain(['admin.example.com', 'admin2.example.com'], function () use ($route) {
    $route->get('dashboard', 'Admin/Index/index');
});

// 获取已绑定的域名
$domains = $route->getDomains();
```

### URL 生成

```php
// 注册命名路由
$route->get('user/:id', 'User/read')->name('user.detail');

// 根据路由名生成 URL
$url = $route->buildUrl('user.detail', ['id' => 123]);

// 生成结果: /user/123
```

### 路由分发

```php
// 处理请求分发
$response = $route->dispatch($request);

// 检查路由匹配
$result = $route->check('user/123');
```

### 路由配置

```php
// 获取路由配置
$config = $route->config();

// 获取指定配置项
$lazy = $route->config('url_lazy_route');

// 设置懒加载
$route->lazy(true);

// 设置正则合并
$route->mergeRuleRegex(true);
```

## 路由规则

### 变量规则

```php
// 基本变量
$route->get('user/:id', 'User/read');

// 可选变量
$route->get('user/[:id]', 'User/index');

// 多变量
$route->get('user/:id/post/:post_id', 'User/post');

// 正则约束
$route->get('user/:id', 'User/read')->pattern(['id' => '\d+']);
```

### 路由参数

```php
// 后缀限制
$route->get('user/:id', 'User/read')->ext('html');

// 禁止后缀
$route->get('user/:id', 'User/read')->denyExt('php');

// 请求域名限制
$route->get('user/:id', 'User/read')->domain('api.example.com');

// 请求方法限制
$route->get('user/:id', 'User/read')->method('GET');
```

## Trait 职责说明

### Route 类 Trait

| Trait | 职责 | 主要方法 |
|-------|------|----------|
| RouteRegister | 路由注册 | get, post, put, delete, patch, head, options, any, rule |
| RouteGroup | 路由分组 | group, module, pattern, option, auto |
| RouteResource | 资源路由 | resource, rest, getRest |
| RouteDomain | 域名路由 | domain, getDomains, checkDomain |
| RouteDispatch | 路由分发 | dispatch, check, checkUrlDispatch |
| RouteUrl | URL 生成 | buildUrl |

### RuleGroup 类 Trait

| Trait | 职责 | 主要方法 |
|-------|------|----------|
| RuleManager | 规则管理 | addRule, addRuleItem, miss, getMissRule, getRules, clear |
| RuleMatcher | 规则匹配 | check, checkUrl, checkMergeRuleRegex |
| RuleBinder | 路由绑定 | auto, class, controller, namespace, module, layer, checkBind |
| RuleConfig | 路由配置 | prefix, alias, mergeRuleRegex, dispatcher, getFullName |

## 核心类说明

### Route 类

路由核心类，整合所有 Trait 提供完整的路由功能。

```php
class Route
{
    use RouteRegister,
        RouteGroup,
        RouteResource,
        RouteDomain,
        RouteDispatch,
        RouteUrl;
}
```

### RuleGroup 类

路由分组管理器，支持嵌套分组和参数继承。通过 Trait 实现职责分离。

```php
class RuleGroup extends Rule
{
    use RuleManager,
        RuleMatcher,
        RuleBinder,
        RuleConfig;
}
```

### RuleItem 类

单个路由规则项，包含路由匹配和参数解析逻辑。

### UrlBuild 类

URL 构建器，负责根据路由规则生成 URL。

## 最佳实践

1. **路由命名**：为重要路由设置名称，便于 URL 生成和维护。
2. **参数约束**：使用 pattern 方法对路由参数进行正则约束。
3. **分组管理**：将相关路由组织到分组中，统一配置参数。
4. **懒加载**：开启路由懒加载可以提升性能。
5. **路由缓存**：使用 `route:cache` 命令生成路由缓存。