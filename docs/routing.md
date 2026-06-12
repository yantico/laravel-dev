# 路由自动发现

工具包的核心约定：**Controller 放到 `App\Modules\` 下就自动注册为路由，无需手动配置。**

## 目录结构约定

```
app/Modules/
├── Admin/                                    ← 模块名（作为路由前缀）
│   ├── BaseController.php                    ← 自动跳过（不注册路由）
│   ├── Users/
│   │   └── UserController.php                ← 注册为 /admin/users/user/*
│   └── Orders/
│       └── OrderController.php               ← 注册为 /admin/orders/order/*
└── Employee/                                 ← 另一个模块
    ├── BaseController.php
    └── Collection/
        └── HouseCollectionTasksController.php ← 注册为 /employee/collection/house-collection-tasks/*
```

## Controller 继承链

项目中 Controller 的引用关系如下：

```
Illuminate\Routing\Controller          ← Laravel 框架基础控制器
    └── App\Http\Controllers\Controller   ← 项目基础控制器（空壳，可选）
        └── BaseController                 ← 模块基类（共享方法，自动跳过路由注册）
            └── XxxController              ← 具体业务控制器
```

### 第一层：项目基础控制器

```php
// app/Http/Controllers/Controller.php
namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    // Laravel 默认生成的基类，可以放全局共享的逻辑
}
```

### 第二层：模块基类（BaseController）

每个模块下可以有一个 `BaseController.php`，路由发现时会**自动跳过**（不注册路由）：

```php
// app/Modules/Employee/BaseController.php
namespace App\Modules\Employee;

use App\Http\Controllers\Controller;
use App\Models\Companies;
use LaravelDev\App\Traits\ControllerTrait;

class BaseController extends Controller
{
    use ControllerTrait;

    // 放这个模块内所有 Controller 共享的方法
    // 比如获取当前登录员工、公司信息等

    protected function getCompany(): Companies
    {
        return auth('employee')->user()->company;
    }
}
```

### 第三层：具体业务控制器

```php
// app/Modules/Employee/Collection/HouseCollectionTasksController.php
namespace App\Modules\Employee\Collection;

use App\Modules\Employee\BaseController;

/**
 * @intro 房屋采集任务
 */
class HouseCollectionTasksController extends BaseController
{
    public function list(): mixed
    {
        $company = $this->getCompany();  // 使用模块基类的方法
        // ...
    }
}
```

> **关键点**：文件名以 `Base` 开头或叫 `BaseController.php` 的控制器会被路由发现自动跳过。

## 路由注册规则

以 `App\Modules\Admin\Users\UserController` 为例：

| 规则 | 值 |
|------|-----|
| 路由前缀 | `/admin/users/user` |
| HTTP 方法 | 默认 `POST`，可通过 `@methods` 注解覆盖 |
| 路由名称 | `admin.users.user.{方法名}`（点号分隔） |

假设 Controller 中有以下方法：

```php
class UserController extends Controller
{
    public function list() { ... }       // POST /admin/users/user/list
    public function store() { ... }      // POST /admin/users/user/store
    public function update() { ... }     // POST /admin/users/user/update
    public function show() { ... }       // POST /admin/users/user/show
    public function delete() { ... }     // POST /admin/users/user/delete
}
```

方法名会自动转为 `snake_case` 作为 URI 路径。

## 激活路由发现

在 `routes/api.php` 中调用（推荐）：

```php
<?php
// routes/api.php
use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Services\RouterServices;

try {
    RouterServices::Register();
} catch (Err|ReflectionException $e) {
    logger()->error($e->getMessage());
}
```

> 也可以放在 `AppServiceProvider::boot()` 中调用，效果相同。

## 中间件配置

你需要在 `bootstrap/app.php` 中为模块配置中间件组。`appendToGroup` 的第一个参数必须与 `App\Modules/` 下的目录名一致：

```php
->withMiddleware(function (Middleware $middleware) {
    // 中间件别名
    $middleware->alias([
        'JsonWrapper' => \LaravelDev\App\Middlewares\JsonWrapperMiddleware::class,
    ]);

    // 按模块配置中间件组
    $middleware->appendToGroup('Admin', ['auth:Admin', 'JsonWrapper']);
    $middleware->appendToGroup('Employee', ['auth:Employee', 'JsonWrapper']);
})
```

## 多层目录支持

路由发现支持任意层级的子目录，完整路径都会成为路由前缀：

```
app/Modules/Employee/Collection/HouseCollectionTasksController.php
                 ↑          ↑              ↑
             module    sub-module      controller
```

生成路由前缀：`/employee/collection/house-collection-tasks`
