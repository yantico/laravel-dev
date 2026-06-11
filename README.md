# Laravel Dev

<p align="center">
<strong>Laravel 12/13 快速开发工具包</strong>
</p>

<p align="center">
基于 <strong>约定优于配置</strong> 的理念，通过数据库表结构自动生成 Model、Controller、Migration、Enum、Test，<br>
解析 Controller 源码自动产出 OpenAPI 文档和 ER 图，内置 Builder 宏、统一异常处理、权限校验等常用能力。
</p>

<p align="center">
<a href="https://packagist.org/packages/yantico/laravel-dev"><img src="https://img.shields.io/packagist/v/yantico/laravel-dev.svg?style=flat-square" alt="Latest Version on Packagist"></a>
<a href="https://packagist.org/packages/yantico/laravel-dev"><img src="https://img.shields.io/packagist/dt/yantico/laravel-dev.svg?style=flat-square" alt="Total Downloads"></a>
<img src="https://img.shields.io/badge/PHP-%5E8.2-777BB4?style=flat-square" alt="PHP Version">
<img src="https://img.shields.io/badge/Laravel-%5E12.0%20%7C%20%5E13.0-FF2D20?style=flat-square" alt="Laravel Version">
<img src="https://img.shields.io/badge/license-MIT-green?style=flat-square" alt="License">
</p>

---

## 目录

- [核心能力](#核心能力)
- [环境要求](#环境要求)
- [安装](#安装)
- [5 分钟快速上手](#5-分钟快速上手)
- [项目配置详解](#项目配置详解)
- [Artisan 命令大全](#artisan-命令大全)
  - [缓存命令](#缓存命令)
  - [代码生成命令](#代码生成命令)
  - [调试命令](#调试命令)
  - [工具命令](#工具命令)
- [路由自动发现](#路由自动发现)
- [Model 生成（双层模式）](#model-生成双层模式)
- [Controller 生成](#controller-生成)
- [Enum 生成](#enum-生成)
- [Migration 生成](#migration-生成)
- [Test 生成](#test-生成)
- [Builder 宏方法](#builder-宏方法)
- [Traits 工具集](#traits-工具集)
- [中间件](#中间件)
- [辅助工具类](#辅助工具类)
- [异常处理](#异常处理)
- [API 在线文档](#api-在线文档)
- [数据库注释约定](#数据库注释约定)
- [DocBlock 注解约定](#docblock-注解约定)
- [完整开发流程](#完整开发流程)
- [依赖说明](#依赖说明)

---

## 核心能力

| 能力 | 说明 |
|------|------|
| **代码生成** | 从数据库表结构一键生成 Model（Base + 可编辑双层）、Controller（CRUD）、Enum、Migration、Test |
| **路由自动发现** | Controller 放到 `App\Modules\` 即自动注册路由，无需手动写 `routes/*.php` |
| **OpenAPI 文档** | 解析 Controller 源码中的 `request()->validate()` 和 DocBlock 注解，零配置产出 OpenAPI 3.1 规范 |
| **ER 图** | 基于 PlantUML 自动生成数据库表关系图，按业务分组 |
| **Builder 宏** | `ifWhere`、`ifWhereLike`、`page`、`order` 等条件链式查询，告别重复的 `if ($params['xxx'])` 判断 |
| **统一响应** | `JsonWrapperMiddleware` 自动包装 `{success, data}` 格式，分页自动加 `meta` |
| **异常处理** | `ee()` 全局抛异常 + `ExceptionRender` 统一渲染 JSON 错误响应 |

---

## 环境要求

| 依赖 | 版本 |
|------|------|
| PHP | >= 8.2 |
| Laravel | >= 12.0 或 >= 13.0 |

---

## 安装

```bash
composer require yantico/laravel-dev
```

发布配置文件和 API 文档前端资源到项目中：

```bash
php artisan vendor:publish --tag=laravel-dev
```

发布后会在你的项目中生成：

```
config/project.php     ← 包配置文件（可自定义）
public/docs/            ← API 在线文档前端页面
```

---

## 5 分钟快速上手

假设你已经有一个正在运行的 Laravel 项目，并且数据库中有一张 `users` 表。

### 第 1 步：配置 `config/project.php`

发布后编辑配置文件，按需调整（大部分保持默认即可）：

```php
return [
    'showDoc' => env('SHOW_DOC', true),  // 生产环境建议设为 false
    'perPageAllow' => [10, 20, 50, 100],
];
```

### 第 2 步：构建缓存

工具包通过反射数据库和代码来工作，首次使用前需要构建缓存：

```bash
php artisan cdb    # 缓存数据库表结构（本地环境会自动刷新，无需重复执行）
```

### 第 3 步：生成代码

```bash
# 为 users 表生成 Model（Base + 可编辑类）
php artisan gd users

# 生成 Controller（放在 Admin 模块下）
php artisan gc Admin/Users

# 生成 Enum（如 users 表有个 type 字段）
php artisan ge users/type
```

### 第 4 步：注册路由

在你的 `AppServiceProvider::boot()` 或专门的服务提供者中调用：

```php
use LaravelDev\App\Services\RouterServices;

public function boot(): void
{
    RouterServices::Register();
}
```

所有放在 `App\Modules\` 下的 Controller 会自动注册为 API 路由。

### 第 5 步：查看 API 文档

启动项目后访问：

```
http://your-app.test/docs/index.html
```

前端会自动从 `/api/docs/openapi` 获取 OpenAPI 规范并渲染。你可以看到所有接口的参数、响应示例和数据库表结构。

**就这样！** 一个完整的 CRUD 接口 + 在线文档就绪了。

---

## 项目配置详解

`config/project.php` 是本工具包的核心配置，控制代码生成行为、文档展示、权限校验等：

```php
return [
    // ===== 分页 =====
    // 允许的分页大小，用于 Builder 宏 page() 和 ControllerTrait::perPage()
    'perPageAllow' => [10, 20, 50, 100],

    // ===== 数据库备份 =====
    // php artisan db:backup 会把这些表的数据导出为 Seed 文件
    'dbBackupList' => [
        'sys_permissions',
        'sys_roles',
        'personal_access_tokens',
    ],

    // ===== Migration 重命名 =====
    // php artisan Rename 时跳过匹配这些模式的文件
    'migrationBlacklists' => [],

    // ===== Model 生成 =====
    // gam 命令跳过这些表（不生成 Model）
    'dbSkipGenModel' => ['cache', 'sessions', 'jobs', 'failed_jobs'],

    // 自动为这些表的 Model 添加 HasApiTokens trait
    'hasApiTokens' => ['admins', 'wechats'],

    // 自动为这些表的 Model 添加 HasRoles trait
    'hasRoles' => ['sys_permissions', 'sys_roles'],

    // 自动为这些表的 Model 添加 Kalnoy\NodeTrait（嵌套集合/树形结构）
    'hasNodeTrait' => ['categories'],

    // ===== 在线文档 =====
    // 是否注册文档路由（/api/docs/openapi、/api/docs/plantuml）
    'showDoc' => env('SHOW_DOC', true),

    // ===== 权限校验 =====
    // 这些模块下的接口会启用 CheckPermissionMiddleware
    'enableCheckPermissionModules' => ['Admin'],

    // ===== ER 图 =====
    // PlantUML 渲染服务器地址
    'plantUmlServer' => 'https://www.plantuml.com/plantuml/svg/',

    // ER 图分组（每组包含一组相关联的表）
    'erMaps' => [
        '用户体系' => ['users', 'user_profiles', 'user_logs'],
        '订单系统' => ['orders', 'order_items', 'products'],
    ],
];
```

---

## Artisan 命令大全

### 缓存命令

这些命令将数据库/代码的元数据缓存到 `storage/framework/cache/`，供其他命令和在线文档使用。

> **本地环境**：缓存会自动刷新，无需手动执行。**生产环境**：部署后需手动执行一次。

| 命令 | 说明 | 何时执行 |
|------|------|----------|
| `php artisan cdb` | 反射所有数据库表结构，缓存为元数据 | 数据库结构变更后 |
| `php artisan ce` | 反射 `app/Enums/` 下所有枚举类，缓存元数据 | 新增/修改枚举后 |
| `php artisan cr` | 扫描 `app/Modules/` 下所有 Controller，缓存路由元数据 | 新增/修改 Controller 后 |

### 代码生成命令

所有生成命令支持 `-f`（`--force`）参数强制覆盖已存在的文件。

| 命令 | 参数格式 | 说明 | 生成位置 |
|------|----------|------|----------|
| `php artisan gam` | 无 | 为**所有**表批量生成 Model | `app/Models/` |
| `php artisan gd {name}` | 表名，如 `users` | 为单张表生成 Model（Base + 可编辑类） | `app/Models/Base/` + `app/Models/` |
| `php artisan gc {name}` | 模块路径，如 `Admin/Users` | 生成 CRUD Controller | `app/Modules/{模块路径}/` |
| `php artisan ge {name}` | `表名/字段名`，如 `users/type` | 生成 Enum 类 | `app/Enums/` |
| `php artisan gm {name}` | 表名，如 `users` | 生成 Migration 文件 | `database/migrations/` |
| `php artisan gt {name}` | 模块路径，如 `Admin/Users` | 生成 PHPUnit 测试类 | `tests/Modules/{模块路径}/` |
| `php artisan ger` | 无 | 生成 PlantUML ER 图 | `database/maps/` |

### 调试命令

用于查看元数据和生成代码模板，不会写入任何文件。

| 命令 | 参数格式 | 说明 |
|------|----------|------|
| `php artisan ddb {name}` | 表名，如 `User` 或 `users` | 输出表的完整元数据 JSON（列、类型、关系等） |
| `php artisan de {name}` | 枚举类名，如 `UserTypeEnum` | 输出枚举的元数据 JSON |
| `php artisan dr {name}` | 模块路径，如 `Admin/Users` | 输出 Controller 的路由元数据 |
| `php artisan dt {name}` | 表名，如 `users` | 输出代码模板片段（`$fillable`、验证规则、插入模板），方便复制粘贴 |

### 工具命令

| 命令 | 说明 |
|------|------|
| `php artisan db:backup` | 将 `config('project.dbBackupList')` 中的表数据导出为 Seed 文件（基于 `iseed`） |
| `php artisan Rename` | 重命名 `database/migrations/` 中的文件，统一日期前缀便于排序 |

---

## 路由自动发现

工具包的核心约定：**Controller 放到 `App\Modules\` 下就自动注册为路由，无需手动配置。**

### 目录结构约定

```
app/Modules/
├── Admin/                          ← 模块名（作为路由前缀和中间件组名）
│   ├── BaseController.php          ← 自动跳过（不注册路由）
│   ├── Users/
│   │   └── UserController.php      ← 注册为 /admin/users/user/*
│   └── Orders/
│       └── OrderController.php     ← 注册为 /admin/orders/order/*
└── Api/                            ← 另一个模块
    └── Products/
        └── ProductController.php   ← 注册为 /api/products/product/*
```

### 路由注册规则

以 `App\Modules\Admin\Users\UserController` 为例：

| 规则 | 值 |
|------|-----|
| 路由前缀 | `/admin/users/user` |
| 中间件组 | `admin`（取第一个模块名） |
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

### 激活路由发现

在你的 `AppServiceProvider::boot()` 中调用：

```php
use LaravelDev\App\Services\RouterServices;

public function boot(): void
{
    RouterServices::Register();
}
```

### 中间件配置

`Register()` 会以第一个模块名（如 `admin`）作为中间件组名。你需要在 `bootstrap/app.php` 中配置对应的中间件组：

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('admin', [
        \LaravelDev\App\Middlewares\JsonWrapperMiddleware::class,
        // 'auth:admin',  // 根据需要添加认证中间件
    ]);
})
```

---

## Model 生成（双层模式）

`gd` 命令为每张表生成**两个** Model 文件：

### Base Model（自动生成，可覆盖）

位于 `app/Models/Base/BaseUser.php`，包含从数据库反射出来的所有属性：

```php
namespace App\Models\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
// 其他自动导入的 trait/关联类...

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $phone
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class BaseUser extends Base   // ← 继承你项目中的 Base 模型（通常继承 Eloquent Model）
{
    use HasFactory;

    protected $table = 'users';
    protected string $comment = '用户';
    protected $fillable = ['name', 'email', 'password', 'phone'];
    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime'];
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }
}
```

> **前置要求**：使用 `gd` 生成 Model 之前，项目需自行创建 `App\Models\Base\Base` 基类。该基类需继承 `Illuminate\Database\Eloquent\Model` 并 `use ModelTrait`，这是包的设计约定。示例：
>
> ```php
> <?php
> namespace App\Models\Base;
>
> use Illuminate\Database\Eloquent\Model;
> use LaravelDev\App\Traits\ModelTrait;
>
> class Base extends Model
> {
>     use ModelTrait;
>
>     // 所有 Model 共享的逻辑可放在这里（全局作用域、通用方法等）
> }
> ```

### User Model（生成一次，永不覆盖）

位于 `app/Models/User.php`，空类继承 Base，你在这里写业务逻辑：

```php
namespace App\Models;

use App\Models\Base\BaseUser;

class User extends BaseUser
{
    // 在这里添加你的自定义方法、作用域、访问器等
    // 即使重新执行 gd -f users，这个文件也不会被覆盖
}
```

**为什么不直接改 Model？** 因为数据库结构会变，你需要反复执行 `gd -f` 更新 Base 类。双层设计确保你的自定义代码和自动生成的代码互不干扰。

### 命令

```bash
# 为 users 表生成 Model
php artisan gd users

# 强制覆盖 Base Model（User Model 不会被覆盖）
php artisan gd users -f

# 批量为所有表生成 Model（跳过 config 中 dbSkipGenModel 的表）
php artisan gam
```

### 自动识别的表特征

生成 Model 时会根据数据库结构自动处理：

| 特征 | 自动行为 |
|------|----------|
| 有 `deleted_at` 列 | 添加 `SoftDeletes` trait |
| 有 JSON 类型列 | 自动添加到 `$casts` |
| 列注释含 `[hidden]` | 添加到 `$hidden` |
| 列注释含 `[ref:table]` | 生成 `belongsTo` 关联方法 |
| 列注释含 `[enum:ClassName]` | 添加到 `$casts` |
| 表名在 `hasApiTokens` 配置中 | 添加 `HasApiTokens` trait |
| 表名在 `hasRoles` 配置中 | 添加 `HasRoles` trait |
| 表名在 `hasNodeTrait` 配置中 | 添加 `Kalnoy\NodeTrait` |
| 其他表的 `_id` 外键指向本表 | 生成 `hasMany` 关联方法 |

---

## Controller 生成

### 命令

```bash
php artisan gc Admin/Users
```

这会生成 `app/Modules/Admin/Users/UserController.php`。

### 生成内容

根据表是否有 `deleted_at` 列，自动选择标准或软删除模板：

**标准 Controller**（5 个方法）：

```php
namespace App\Modules\Admin\Users;

use App\Models\User;

/**
 * @intro 用户管理
 */
class UserController extends \Illuminate\Routing\Controller
{
    public function list(): mixed   // 分页列表，支持 name 模糊搜索
    public function store(): void   // 创建，含 name 唯一性校验
    public function update(): void  // 更新，含 name 唯一性校验
    public function show(): User    // 按 ID 查看单条
    public function delete(): void  // 按 ID 删除
}
```

**软删除 Controller**（7 个方法）：

```php
// 额外增加：
public function softDelete(): void  // 软删除
public function restore(): void     // 恢复软删除
public function delete(): void      // 强制删除（forceDelete）
```

### 验证规则

`store` 和 `update` 方法的验证规则从数据库列元数据自动生成：

```php
$params = request()->validate([
    'name' => 'required|string',      # 名称
    'email' => 'required|string',     # 邮箱
    'phone' => 'nullable|string',     # 手机号
]);
```

---

## Enum 生成

### 命令

```bash
# 用 "表名/字段名" 格式（推荐）
php artisan ge users/type
# → 生成 App\Enums\UsersTypeEnum

# 直接指定类名
php artisan ge OrderStatus
# → 生成 App\Enums\OrderStatusEnum
```

### 生成内容

```php
namespace App\Enums;

use LaravelDev\App\Traits\EnumTrait;

/**
 * @intro 用户类型
 * @field type
 */
enum UsersTypeEnum: string
{
    use EnumTrait;

    /**
     * @label Label
     * @value Value
     * @color #ff0000
     */
    case Label = 'Value';
}
```

生成后你需要手动修改 case 为实际的枚举值：

```php
enum UsersTypeEnum: string
{
    use EnumTrait;

    /**
     * @label 管理员
     * @value admin
     * @color #ff0000
     */
    case Admin = 'admin';

    /**
     * @label 普通用户
     * @value user
     * @color #1890ff
     */
    case User = 'user';
}
```

`@label`、`@value`、`@color` 注解会被 `ce` 缓存命令反射采集，展示在 OpenAPI 文档中。

---

## Migration 生成

### 命令

```bash
php artisan gm users
```

### 生成内容

在 `database/migrations/` 下生成带时间戳的 Migration 文件：

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('名称');
            $table->string('email')->unique()->comment('邮箱');
            $table->string('password')->comment('密码 [hidden]');
            $table->string('phone')->nullable()->comment('手机号');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

> **提示**：Migration 中的列定义来自缓存中的数据库表结构。如果你是先设计数据库再生成代码，直接用 `gd` 生成 Model 即可。`gm` 更适合将已有数据库导出为 Migration 文件的场景。

---

## Test 生成

### 命令

```bash
php artisan gt Admin/Users
```

### 生成内容

生成 `tests/Modules/Admin/Users/UserControllerTest.php`：

```php
namespace Tests\Modules\Admin\Users;

use Tests\TestCase;

class UserControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testList()
    {
        $this->go(__METHOD__, [
            'name' => '',
        ]);
    }

    public function testStore()
    {
        $this->go(__METHOD__, [
            'name' => '',
            'email' => '',
            'password' => '',
        ]);
    }

    // ... 每个 Controller 方法对应一个测试方法
}
```

### 前置准备：配置 TestCase 基类

生成的测试类继承自 `Tests\TestCase`，`go()` 方法来自 `TestCaseTrait`。你需要在项目的 `tests/TestCase.php` 中引入它：

```php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LaravelDev\App\Traits\TestCaseTrait;

abstract class TestCase extends BaseTestCase
{
    use TestCaseTrait;

    protected function setUp(): void
    {
        parent::setUp();

        # 设置远程测试的服务器地址（默认用本机）
        // $this->baseUrl = 'http://0.0.0.0:8000';

        # 定义每个模块的 token，测试时会自动携带对应的认证头
        $this->tokens = [
            'Admin' => 'Bearer 3085|fGr4SPxpEGznle9m4PZC8GR7Lye5vLcxr6v3QDBK3b7875fd',
        ];
    }
}
```

`TestCaseTrait::go()` 会自动从测试方法名（如 `testStore`）解析出对应的 API URI（如 `/api/admin/users/user/store`）并发送请求。

---

## Builder 宏方法

在 Model 中使用 `ModelTrait` 获得 IDE 自动补全：

```php
use LaravelDev\App\Traits\ModelTrait;

class User extends BaseUser
{
    use ModelTrait;
}
```

### 条件查询宏

这些宏只有在参数中**存在且非空**时才会生效，非常适合处理前端传来的筛选条件：

```php
// 假设 $params 是 request()->validate() 的结果
$params = request()->validate([
    'name' => 'nullable|string',
    'status' => 'nullable|integer',
    'keyword' => 'nullable|string',
    'amount' => 'nullable|array',
    'created_at' => 'nullable|array',
]);

User::query()
    ->ifWhere($params, 'status')                          // status = ?
    ->ifWhereLike($params, 'name')                        // name LIKE %?%
    ->ifWhereLikeKeyword($params, 'keyword', ['name', 'email']) // name LIKE %?% OR email LIKE %?%
    ->ifWhereNumberRange($params, 'amount')               // amount BETWEEN ? AND ?
    ->ifWhereDateRange($params, 'created_at')             // created_at BETWEEN ? AND ?
    ->ifIsNull($params, 'is_deleted', 'deleted_at')       // deleted_at IS NULL
    ->ifHasWhereLike($params, 'role_name', 'roles', 'name') // 关联表 roles.name LIKE %?%
    ->order()                                              // 按 sorter 参数排序（兼容 Ant Design）
    ->page();                                              // 分页
```

### 完整宏列表

| 宏 | 用法 | 说明 |
|-----|------|------|
| `ifWhere($params, $key, $field)` | 等值查询 | `$params[$key]` 非空时 `where($field, $value)` |
| `ifWhereLike($params, $key, $field)` | 模糊查询 | 自动加 `%` 通配符 |
| `ifWhereLikeKeyword($params, $key, $fields)` | 多字段关键词 | OR 搜索多个字段 |
| `ifWhereNumberRange($params, $key, $field)` | 数值范围 | 传入 `[min, max]` 二元数组 |
| `ifWhereDateRange($params, $key, $field, $type)` | 日期范围 | 传入 `[start, end]`，支持 `date` 和 `datetime` 类型 |
| `ifIsNull($params, $key, $field)` | NULL 判断 | `true` → `whereNull`，`false` → `whereNotNull` |
| `ifIsNotNull($params, $key, $field)` | NOT NULL 判断 | 与 `ifIsNull` 相反 |
| `ifHasWhereLike($params, $key, $relation, $field)` | 关联模糊查询 | `whereHas` + LIKE |
| `order($key, $default)` | 排序 | 兼容 Ant Design 的 `sorter` 参数，防 SQL 注入 |
| `page()` | 分页 | 自动读取 `perPage` 参数，校验合法值 |
| `getById($id, $throw, $lock, $msg)` | ID 查找 | 找不到时抛异常，支持行锁 |
| `forSelect($key1, $key2, $orderBy)` | 下拉选项 | 返回 `[[id, name], ...]` 格式 |
| `unique($params, $keys, $label, $field)` | 唯一性校验 | 重复时抛异常，排除自身（编辑场景） |

---

## Traits 工具集

### ControllerTrait —— 控制器通用方法

```php
use LaravelDev\App\Traits\ControllerTrait;

class UserController extends Controller
{
    use ControllerTrait;

    public function store(Request $request)
    {
        $params = $request->validate([
            'name' => 'required|string',
            'password' => 'nullable|string',
        ]);

        // 自动 bcrypt 加密密码，为空则移除该字段
        $this->crypto($params, 'password');

        User::create($params);
    }

    public function list()
    {
        // 从 request 中获取并校验分页大小
        $perPage = $this->perPage(); // 只允许 config('project.perPageAllow') 中的值
    }
}
```

### EnumTrait —— 枚举增强

让你的 PHP Enum 具备标签、颜色、双向查找能力：

```php
use LaravelDev\App\Traits\EnumTrait;

enum UserStatusEnum: string
{
    use EnumTrait;

    /**
     * @label 启用
     * @value active
     * @color #52c41a
     */
    case Active = 'active';

    /**
     * @label 禁用
     * @value disabled
     * @color #ff4d4f
     */
    case Disabled = 'disabled';
}

// 可用方法：
UserStatusEnum::Values();                     // ['active', 'disabled']
UserStatusEnum::GetLabels();                  // ['启用', '禁用']
UserStatusEnum::GetLabelByValue('active');    // '启用'
UserStatusEnum::GetValueByLabel('启用');      // 'active'
UserStatusEnum::IsValueInEnum('active');      // true
UserStatusEnum::GetMaxLength();               // 8（最长值的字符串长度，用于设计数据库字段长度）
UserStatusEnum::Comment('状态');              // '状态:UserStatusEnum'（用于 Migration 列注释）
```

### ModelTrait —— IDE 自动补全

空 trait，仅提供 PHPDoc `@method` 注解，让 IDE 自动补全 Builder 宏方法：

```php
class User extends BaseUser
{
    use ModelTrait; // 加上这个，IDE 就能自动补全 ifWhere、page、getById 等方法
}
```

### TestCaseTrait —— 测试辅助

自动从测试方法名解析 API URI：

```php
use LaravelDev\App\Traits\TestCaseTrait;

class UserControllerTest extends TestCase
{
    use TestCaseTrait;

    public function testStore()
    {
        // __METHOD__ = 'Tests\Modules\Admin\Users\UserControllerTest::testStore'
        // 自动解析为 POST /api/admin/users/user/store
        $this->go(__METHOD__, [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
```

---

## 中间件

### JsonWrapperMiddleware —— 统一响应包装

自动将所有 Controller 返回值包装为标准 JSON 格式：

```php
// 在 bootstrap/app.php 中注册
->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('admin', [
        \LaravelDev\App\Middlewares\JsonWrapperMiddleware::class,
    ]);
})
```

**包装规则：**

| Controller 返回值 | 最终响应 |
|---|---|
| 空（无返回值） | `{"success": true}` |
| `['name' => 'test']` | `{"success": true, "data": {"name": "test"}}` |
| 分页结果（Paginator） | `{"success": true, "data": [...], "meta": {"total": 100, "per_page": 20, "current_page": 1, "last_page": 5}}` |
| 带 `statistics` 键 | `{"success": true, "data": ..., "statistics": ...}` |
| `BinaryFileResponse` / `StreamedResponse` | 不包装，直接返回 |
| 已包含 `success: false` | 不包装，直接返回（避免二次包装错误） |

### CheckPermissionMiddleware —— 接口权限校验

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

## 辅助工具类

### SchemaHelper —— Migration 辅助方法

```php
use LaravelDev\App\Helpers\SchemaHelper;

Schema::create('orders', function (Blueprint $table) {
    $table->id();

    // 枚举列（自动读取枚举值，注释中标记枚举类）
    SchemaHelper::Enum($table, 'status', OrderStatusEnum::class, '订单状态');

    // 外键列（注释中标记关联表）
    SchemaHelper::ForeignId($table, 'user_id', '用户', 'users');

    // 地址字段组（生成 province_code、city_code、area_code、street_code + 4个名称字段）
    SchemaHelper::Cities($table, 'receiver');

    $table->timestamps();
});
```

### FastExcelHelper —— Excel 流式导入导出

```php
use LaravelDev\App\Helpers\FastExcelHelper;

// 导出（基于 cursor，内存友好，适合大数据量）
FastExcelHelper::Export(
    query: Order::query()->where('status', 'completed'),
    filename: '订单列表',
    callback: fn($order) => [
        '订单号' => $order->no,
        '金额' => $order->amount,
        '创建时间' => $order->created_at->toDateString(),
    ],
    ext: 'xlsx'  // 支持 csv / xlsx
);

// 导入（自动事务包裹，任意一行失败全部回滚）
FastExcelHelper::Import($file, function ($row) {
    User::create([
        'name' => $row['姓名'],
        'email' => $row['邮箱'],
    ]);
});
```

### AwsS3Helper —— S3 文件上传

```php
use LaravelDev\App\Helpers\AwsS3Helper;

// 生成预签名上传 URL（前端直传，自动生成 ULID 文件名）
$uploadInfo = AwsS3Helper::PreUpload('avatars', 'photo.jpg');
// 返回: ['url' => 'https://s3.../presigned-url', 'headers' => ['Content-Type' => ...]]

// 生成临时访问 URL
$url = AwsS3Helper::TemporaryUrl('avatars/01HXXX.jpg', 30);

// 富文本中的 Base64 图片 → 上传到 S3 → 替换为 URL（引用传递，直接修改原数组）
AwsS3Helper::ReplaceImageToOss($params, ['content'], 'editor', 800, 80);

// 生成缩略图（引用传递，直接在 $images 中添加 thumbUrl 字段）
AwsS3Helper::CreateThumbUrl($images, 200, 80);
// 支持 阿里云 OSS 和 腾讯云 COS 图片处理参数
```

### IpHelper

```php
use LaravelDev\App\Helpers\IpHelper;

$ip = IpHelper::GetIp(); // 等同于 request()->ip()
```

### LaravelPermissionHelper —— 权限树构建

```php
use LaravelDev\App\Helpers\LaravelPermissionHelper;

// 获取用户的权限树（用于前端菜单/权限展示）
$tree = LaravelPermissionHelper::getPermissionByUser($user);
```

---

## 异常处理

### ee() —— 全局异常抛出

`ee()` 是全局函数，无需 `use`，在任何地方直接调用即可：

```php
// 简单用法（默认 code=999, httpStatus=500）
ee('操作失败');

// 完整用法
ee(
    '余额不足',          // 错误信息
    30001,               // 业务错误码
    '请充值后重试',       // 附加描述（debug 模式下返回）
    2,                   // showType：前端展示方式（1=通知, 2=弹窗等）
    400                  // HTTP 状态码
);
```

### ExceptionRender —— 统一异常渲染

在你的异常处理器中调用：

```php
// app/Exceptions/Handler.php
public function render($request, Throwable $e): JsonResponse
{
    return ExceptionRender::Render($e);
}
```

**自动处理的异常类型：**

| 异常类型 | HTTP 状态码 | code | 行为 |
|----------|-------------|------|------|
| `Err`（本包自定义） | 自定义 | 自定义 | 使用 `ee()` 传入的值 |
| `AuthenticationException` | 401 | 10000 | "用户未登录" |
| `ValidationException` | 400 | 999 | 列出校验失败的字段 |
| `NotFoundHttpException` | 404 | 404 | "接口不存在" |
| `MethodNotAllowedHttpException` | 405 | 405 | "请求方法不允许" |
| 其他 | 500 | 500 | 通用服务器错误 |

**所有错误响应格式统一：**

```json
{
    "success": false,
    "errorCode": 30001,
    "errorMessage": "余额不足",
    "showType": 2,
    "description": "请充值后重试"
}
```

`debug` 模式下会额外返回请求信息和堆栈跟踪。

---

## API 在线文档

### 开启/关闭

```php
// config/project.php
'showDoc' => env('SHOW_DOC', true),  // 生产环境设为 false
```

### 访问地址

```
http://your-app.test/docs/index.html
```

### 后端路由

| 方法 | URI | 说明 |
|------|-----|------|
| GET | `/api/docs/openapi` | 返回完整 OpenAPI 3.1 JSON（含接口、数据库表结构、枚举定义） |
| GET | `/api/docs/plantuml?name=xxx` | 返回指定分组的 PlantUML ER 图 |

### 文档内容来源

在线文档包含三部分信息：

1. **API 接口** —— 来自 `cr` 缓存，解析 Controller 源码中的 `request()->validate()` 和 DocBlock 注解
2. **数据库表结构** —— 来自 `cdb` 缓存，展示每张表的列、类型、注释
3. **枚举定义** —— 来自 `ce` 缓存，展示枚举值、标签、颜色

---

## 数据库注释约定

工具包通过 Migration 列注释中的特殊标记传递元数据，这些标记会影响代码生成和文档输出：

```php
Schema::create('users', function (Blueprint $table) {
    // [hidden] → 生成 Model 时加入 $hidden 数组（如密码字段）
    $table->string('password')->comment('密码 [hidden]');

    // [enum:ClassName] → 生成 Model 时自动 cast 到枚举类
    $table->string('type')->comment('类型 [enum:UserTypeEnum]');

    // [ref:table] 或 [ref:table,column] → 生成 belongsTo 关联方法
    $table->unsignedBigInteger('user_id')->comment('用户ID [ref:users]');
    $table->unsignedBigInteger('role_id')->comment('角色ID [ref:roles,id]');

    // 普通注释 → 仅作文档说明
    $table->string('name')->comment('用户名');
});
```

---

## DocBlock 注解约定

Controller 方法通过 DocBlock 注解控制路由行为和文档输出：

```php
class UserController extends Controller
{
    /**
     * @intro 用户列表        → 接口描述（显示在文档中）
     * @methods GET,POST      → 指定 HTTP 方法（默认 POST）
     * @responseJson {}       → 示例响应 JSON（显示在文档中）
     * @responseBody {}       → 响应体定义（显示在文档中）
     */
    public function list(): mixed
    {
        // ...
    }

    /**
     * @intro 内部方法
     * @skipInRouter true     → 不注册路由（内部方法）
     */
    public function internalMethod(): void
    {
        // 不会生成路由
    }

    /**
     * @intro 上传文件
     * @withoutMiddlewares auth:admin  → 这个方法跳过指定中间件
     */
    public function upload(): void
    {
        // ...
    }
}
```

**请求参数自动解析：** 工具包会解析方法源码中的 `request()->validate([...])` 或 `$request->validate([...])` 块，自动提取参数名、类型、是否必填和注释：

```php
public function store(): void
{
    $params = request()->validate([
        'name' => 'required|string',      # 姓名（自动提取为必填 string 参数，注释为"姓名"）
        'email' => 'required|string',     # 邮箱
        'phone' => 'nullable|string',     # 手机号（nullable → 非必填）
    ]);
}
```

---

## 完整开发流程

以下是从零开始一个新功能模块的推荐工作流：

### 场景：新增一个「商品管理」模块

```
┌─────────────────────────────────────────────────┐
│  1. 设计数据库                                    │
│     编写 Migration 创建 products 表               │
│     php artisan migrate                          │
├─────────────────────────────────────────────────┤
│  2. 构建缓存                                      │
│     php artisan cdb                              │
├─────────────────────────────────────────────────┤
│  3. 生成代码                                      │
│     php artisan gd products          → Model     │
│     php artisan gc Admin/Products    → Controller│
│     php artisan ge products/status   → Enum      │
│     php artisan gt Admin/Products    → Test      │
├─────────────────────────────────────────────────┤
│  4. 编写业务逻辑                                  │
│     修改 app/Models/Product.php（自定义方法）     │
│     修改 app/Modules/Admin/Products/             │
│           ProductController.php（业务逻辑）      │
├─────────────────────────────────────────────────┤
│  5. 刷新缓存 + 查看文档                           │
│     php artisan cr                               │
│     访问 /docs/index.html 查看接口文档            │
├─────────────────────────────────────────────────┤
│  6. 测试                                         │
│     php artisan test --filter=ProductController  │
├─────────────────────────────────────────────────┤
│  7. 数据库变更时                                   │
│     php artisan cdb                              │
│     php artisan gd products -f  → 更新 Base Model│
│     （Product.php 不会被覆盖）                     │
└─────────────────────────────────────────────────┘
```

---

## 依赖说明

本工具包内置了以下 Laravel 生态常用包，安装 `yantico/laravel-dev` 后无需再单独引入：

| 包 | 用途 |
|---|---|
| `mews/captcha` | 图形验证码生成 |
| `mews/purifier` | HTML 内容净化（防 XSS） |
| `orangehill/iseed` | 数据库表数据导出为 Seed 文件 |
| `kalnoy/nestedset` | 嵌套集合（树形结构，如分类、菜单） |
| `league/flysystem-aws-s3-v3` | AWS S3 / 阿里云 OSS / 腾讯云 COS 文件存储 |
| `rap2hpoutre/fast-excel` | 轻量级 Excel/CSV 导入导出 |
| `spatie/laravel-permission` | RBAC 角色权限管理 |
| `spatie/eloquent-sortable` | 模型排序 |
| `spatie/laravel-tags` | 标签管理 |
| `vinkla/hashids` | ID 哈希混淆 |
| `jawira/plantuml-encoding` | PlantUML 编码（ER 图） |

## License

[MIT](LICENSE)
