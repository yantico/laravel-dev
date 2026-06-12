# 中间件

## 完整配置

在 `bootstrap/app.php` 中配置路由、中间件和异常处理：

```php
<?php

use App\Http\Middleware\ApiKeyAuthenticate;
use App\Http\Middleware\CheckCompanyPermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use LaravelDev\App\Exceptions\ErrConst;
use LaravelDev\App\Exceptions\ExceptionRender;
use LaravelDev\App\Middlewares\JsonWrapperMiddleware;
use LaravelDev\App\Middlewares\CheckPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            // 加载第三方服务路由
            //require base_path('routes/third_party.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 禁止未登录用户访问跳转到login的route
        $middleware->redirectGuestsTo(function () {
            ee(...ErrConst::UserNotLoggedIn);
        });

        # 中间件别名
        $middleware->alias([
            'JsonWrapper' => JsonWrapperMiddleware::class,
            //'CheckPermission' => CheckPermissionMiddleware::class,
            'CheckCompanyPermission' => CheckCompanyPermission::class,
            'ApiKeyAuthenticate' => ApiKeyAuthenticate::class,
        ]);

        $middleware->appendToGroup('Admin', ['auth:Admin', 'JsonWrapper']);
        $middleware->appendToGroup('Employee', ['auth:Employee', 'JsonWrapper']);
        $middleware->appendToGroup('Customer', ['auth:Customer', 'JsonWrapper']);
        $middleware->appendToGroup('Company', ['auth:Company', 'JsonWrapper', 'CheckCompanyPermission']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 禁止输出错误详细信息到日志
        $exceptions->reportable(function (Throwable $e) {})->stop();

        // 控制器中，全局统一错误响应
        $exceptions->renderable(function (Throwable $e) {
            return ExceptionRender::Render($e);
        });
    })->create();
```

### 中间件组说明

| 模块 | Guard 认证 | Json 包装 | 额外中间件 | 典型场景 |
|------|-----------|----------|-----------|---------|
| `Admin` | `auth:Admin` | ✅ | — | 后台管理 |
| `Employee` | `auth:Employee` | ✅ | — | 员工端 |
| `Customer` | `auth:Customer` | ✅ | — | 客户端 |
| `Company` | `auth:Company` | ✅ | `CheckCompanyPermission` | 企业端（含公司权限校验） |
| `ThirdParty` | — | ✅ | `ApiKeyAuthenticate` | 第三方对接（API Key 认证） |

> **Laravel 12 和 13 的中间件 API 完全一致**，`appendToGroup`、`alias`、`redirectGuestsTo` 从 Laravel 11 引入后没有变化。

---

## JsonWrapperMiddleware —— 统一响应包装

**包装规则：**

| Controller 返回值 | 最终响应 |
|---|---|
| 空（无返回值） | `{"success": true}` |
| `['name' => 'test']` | `{"success": true, "data": {"name": "test"}}` |
| 分页结果（Paginator） | `{"success": true, "data": [...], "meta": {"total": 100, "per_page": 20, "current_page": 1, "last_page": 5}}` |
| 带 `statistics` 键 | `{"success": true, "data": ..., "statistics": ...}` |
| `BinaryFileResponse` / `StreamedResponse` | 不包装，直接返回 |
| 已包含 `success: false` | 不包装，直接返回（避免二次包装错误） |

---

## CheckPermissionMiddleware —— 接口权限校验

基于 Spatie Laravel Permission 实现。校验流程：

1. 从 URI 解析模块名 → 确定认证 Guard
2. 获取当前用户的所有权限
3. 检查当前 URI 是否在权限的 `backend_apis` 列表中
4. 不在列表中 → 抛出 403

```php
// 只对 config('project.enableCheckPermissionModules') 中的模块启用
// 例如只对 Admin 模块启用：
'enableCheckPermissionModules' => ['Admin'],
```

---

## CheckCompanyPermission —— 公司权限校验

校验企业用户是否有权操作当前数据。通常配合 `Company` 模块中间件组使用：

```php
$middleware->appendToGroup('Company', ['auth:Company', 'JsonWrapper', 'CheckCompanyPermission']);
```

## ApiKeyAuthenticate —— API Key 认证

适用于无用户概念的第三方接口对接。请求需携带 API Key 进行身份验证：

```php
$middleware->appendToGroup('ThirdParty', ['JsonWrapper', 'ApiKeyAuthenticate']);
```
