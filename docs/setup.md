# 项目基本配置

安装 `yantico/laravel-dev` 后，需要按以下步骤编辑项目的基础文件。

> **Laravel 12 和 13 的 `bootstrap/app.php` 中间件 API 完全一致**（`appendToGroup`、`alias`、`redirectGuestsTo` 从 Laravel 11 引入后未变），以下配置同时适用于两个版本。

---

## 1. 新建 Model 基类

> **前置要求**：在使用 `gd` 命令生成 Model 之前，必须先创建此文件。

```php
<?php
// app/Models/Base/Base.php
namespace App\Models\Base;

use App\Models\Companies;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LaravelDev\App\Traits\ModelTrait;

/**
 * 全局共用的 scope 可以写在这里！
 * @method static ifCompany(Companies $company)
 */
class Base extends Model
{
    use HasFactory, ModelTrait;

    /**
     * @param $query
     * @param Companies $company
     * @return void
     */
    public function scopeIfCompany($query, Companies $company): void
    {
        $query->where('companies_id', $company->id);
    }
}
```

**这个文件的作用：**

- 继承 Eloquent Model 并引入 `ModelTrait`（提供 Builder 宏的 IDE 自动补全）
- 放所有 Model 共享的全局 scope（如 `scopeIfCompany`）
- 放所有 Model 共享的 `@method` 注解（IDE 自动补全更友好）
- 只需创建一次，不会被任何命令覆盖

> 更多关于三层继承模式的说明，请查看 [Model 生成](model.md)。

---

## 2. 编辑项目基础控制器

```php
<?php
// app/Http/Controllers/Controller.php
namespace App\Http\Controllers;

use LaravelDev\App\Traits\ControllerTrait;

abstract class Controller
{
    // 封装了一些简单的方法
    use ControllerTrait;
}
```

> 主要是引入 `ControllerTrait`，提供 `perPage()`、`validate()` 等便捷方法。

---

## 3. 编辑 `bootstrap/app.php`

这是**最关键的配置文件**，控制中间件、异常处理和路由：

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
            // require base_path('routes/third_party.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 禁止未登录用户访问时，抛出异常而不是重定向到 login 路由
        $middleware->redirectGuestsTo(function () {
            ee(...ErrConst::UserNotLoggedIn);
        });

        // 注册中间件别名
        $middleware->alias([
            'JsonWrapper' => JsonWrapperMiddleware::class,
            // 'CheckPermission' => CheckPermissionMiddleware::class,
            'CheckCompanyPermission' => CheckCompanyPermission::class,
            'ApiKeyAuthenticate' => ApiKeyAuthenticate::class,
        ]);

        // 按模块配置中间件组
        // 每个 appendToGroup 的第一个参数对应 App\Modules\ 下的模块名
        $middleware->appendToGroup('Admin', ['auth:Admin', 'JsonWrapper']);
        $middleware->appendToGroup('Employee', ['auth:Employee', 'JsonWrapper']);
        $middleware->appendToGroup('Customer', ['auth:Customer', 'JsonWrapper']);
        $middleware->appendToGroup('Company', ['auth:Company', 'JsonWrapper', 'CheckCompanyPermission']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 禁止输出错误详细信息到日志
        $exceptions->reportable(function (Throwable $e) {})->stop();

        // 全局统一错误响应
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
| `Company` | `auth:Company` | ✅ | `CheckCompanyPermission`（公司权限校验） | 企业端 |
| `ThirdParty` | — | ✅ | `ApiKeyAuthenticate`（API Key 认证） | 第三方对接 |

### 关键配置说明

- **`redirectGuestsTo`**：未登录用户访问需认证的接口时，抛出 `ee()` 异常返回 JSON，而不是 Laravel 默认的重定向到 `/login`
- **`JsonWrapper`**：自动将 Controller 返回值包装为 `{success, data}` 格式，详见 [中间件文档](middleware.md)
- **`CheckCompanyPermission`**：校验企业用户是否有权限操作当前数据
- **`ApiKeyAuthenticate`**：第三方 API Key 认证，适用于无用户的接口
- **`appendToGroup` 的第一个参数必须与 `App\Modules/` 下的目录名一致**

---

## 4. 编辑 `routes/api.php`

将自动路由注册放入 `routes/api.php`：

```php
<?php
// routes/api.php

use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Services\RouterServices;

try {
    // 自动生成路由，通过解析 Controller 源码中的注解来生成
    RouterServices::Register();
} catch (Err|ReflectionException $e) {
    logger()->error($e->getMessage());
}
```

> 也可以放在 `AppServiceProvider::boot()` 中调用，效果相同。

---

## 5. 编辑测试基类

```php
<?php
// tests/TestCase.php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LaravelDev\App\Traits\TestCaseTrait;

abstract class TestCase extends BaseTestCase
{
    use TestCaseTrait;

    protected function setUp(): void
    {
        parent::setUp();

        // 设置远程测试的服务器地址
        // 默认用本机，且不需要 php artisan serve 开启
        // 如果需要测试线上环境，取消下面这行注释：
        // $this->baseUrl = 'http://0.0.0.0:8000';

        // 定义每个模块的 token，在不同模块测试时会自动带上对应的 token
        $this->tokens = [
            'Admin' => '1|foRhxkugzMZCYU6Dww56lLNf8WlkOfI0AR1Yhamme7731300',
            'Company' => '2|9B7nXKR5CPsSlOeMhwEer8ynYC5giFFlOIuMvFp294fe2bd0',
        ];
    }
}
```

---

## 6. 编辑 `.env`

根据项目情况配置以下环境变量：

```env
# 时区
APP_TIMEZONE=Asia/Shanghai
APP_URL=http://localhost:8000

# 语言
APP_LOCALE=cn
APP_FALLBACK_LOCALE=cn
APP_FAKER_LOCALE=zh_CN

# 日志（daily 按天切割 + stderr 输出到控制台）
LOG_STACK=daily,stderr

# 数据库
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=root
DB_PASSWORD=your_password
```

---

## 配置文件清单

| 文件 | 操作 | 说明 |
|------|------|------|
| `app/Models/Base/Base.php` | **新建** | Model 全局基类，引入 ModelTrait，放共享 scope |
| `app/Http/Controllers/Controller.php` | 编辑 | 引入 ControllerTrait |
| `bootstrap/app.php` | 编辑 | 中间件组、异常处理（最关键） |
| `routes/api.php` | 编辑 | 调用 RouterServices::Register() 自动发现路由 |
| `tests/TestCase.php` | 编辑 | 引入 TestCaseTrait，配置测试 token |
| `.env` | 编辑 | 时区、语言、日志、数据库等 |
| `config/project.php` | 编辑 | 包配置（发布后自动生成） |
