# Laravel Dev

<p align="center">
<strong>Laravel 13 快速开发工具包</strong> —— 代码生成、API 文档自动生成、数据库 ER 图、Builder 宏、枚举工具集
</p>

<p align="center">
<a href="https://packagist.org/packages/yantico/laravel-dev"><img src="https://img.shields.io/packagist/v/yantico/laravel-dev.svg?style=flat-square" alt="Latest Version on Packagist"></a>
<a href="https://packagist.org/packages/yantico/laravel-dev"><img src="https://img.shields.io/packagist/dt/yantico/laravel-dev.svg?style=flat-square" alt="Total Downloads"></a>
<img src="https://img.shields.io/badge/PHP-%5E8.3-777BB4?style=flat-square" alt="PHP Version">
<img src="https://img.shields.io/badge/Laravel-%5E13.0-FF2D20?style=flat-square" alt="Laravel Version">
<img src="https://img.shields.io/badge/license-MIT-green?style=flat-square" alt="License">
</p>

---

## 特性

- 🚀 **代码自动生成** —— 从数据库表结构自动生成 Model、Controller、Enum、Migration、Test
- 📖 **OpenAPI 文档自动生成** —— 解析 Controller 源码，零注解自动产出 OpenAPI 3.1 规范
- 🗺️ **ER 图生成** —— 基于 PlantUML 自动生成数据库 ER 关系图
- 🔧 **Builder 宏** —— 条件查询、分页、排序等常用链式调用
- 🛡️ **统一异常处理** —— 标准化 JSON 错误响应
- 🔐 **权限中间件** —— 基于 Spatie Permission 的接口权限校验
- 📦 **S3 文件上传** —— 阿里云/腾讯云图片处理、缩略图、预签名上传
- 📊 **Excel 导入导出** —— 流式内存高效的 CSV/Excel 处理

## 安装

```bash
composer require yantico/laravel-dev
```

发布配置文件和文档前端资源：

```bash
php artisan vendor:publish --tag=laravel-dev
```

这会将以下文件发布到你的项目中：

- `config/project.php` —— 包配置文件
- `public/docs/` —— API 文档前端页面

## 快速开始

### 1. 配置

编辑 `config/project.php`，根据项目需要调整配置项：

```php
return [
    // 允许的分页大小
    'perPageAllow' => [10, 20, 50, 100],

    // 需要生成 HasApiTokens 的表
    'hasApiTokens' => ['admins', 'wechats'],

    // 需要生成 HasRoles 的表
    'hasRoles' => ['sys_permissions', 'sys_roles'],

    // 是否展示文档（建议生产环境关闭）
    'showDoc' => env('SHOW_DOC', true),

    // 需要权限校验的模块
    'enableCheckPermissionModules' => ['Admin'],

    // ER 图分组
    'erMaps' => [
        'standard' => ['users', 'orders', 'products'],
    ],
];
```

### 2. 构建缓存

在使用代码生成和文档功能前，需要先构建元数据缓存：

```bash
# 缓存数据库表结构
php artisan cdb

# 缓存枚举类
php artisan ce

# 缓存路由元数据
php artisan cr
```

### 3. 生成代码

```bash
# 为所有表生成 Model 文件
php artisan gam

# 为单张表生成 Model（Base + 可编辑类）
php artisan gd users

# 生成 Controller
php artisan gc Admin/Users

# 生成 Enum 类
php artisan ge users/type

# 生成 Migration
php artisan gm users

# 生成测试文件
php artisan gt Admin/Users
```

---

## Artisan 命令一览

### 缓存命令

| 命令 | 说明 |
|------|------|
| `php artisan cdb` | 反射数据库表结构并缓存 |
| `php artisan ce` | 反射枚举类并缓存 |
| `php artisan cr` | 扫描 Controller 并缓存路由元数据 |

### 代码生成命令

| 命令 | 说明 |
|------|------|
| `php artisan gam` | 为所有表生成 Model 文件 |
| `php artisan gd {name}` | 为指定表生成 Model（含 Base 类） |
| `php artisan gc {name}` | 生成 Controller（支持 `-f` 强制覆盖） |
| `php artisan ge {name}` | 生成 Enum 类（格式：`table/field`） |
| `php artisan gm {name}` | 生成 Migration 文件 |
| `php artisan gt {name}` | 生成 PHPUnit 测试类 |
| `php artisan ger` | 生成 PlantUML ER 图 |

### 调试命令

| 命令 | 说明 |
|------|------|
| `php artisan ddb {name}` | 查看表的元数据 JSON |
| `php artisan de {name}` | 查看枚举的元数据 JSON |
| `php artisan dr {name}` | 查看 Controller 的路由元数据 |
| `php artisan dt {name}` | 输出表的代码模板（$fillable、验证规则等） |

### 其他命令

| 命令 | 说明 |
|------|------|
| `php artisan db:backup` | 将数据库表导出为 Seed 文件 |
| `php artisan Rename` | 重命名 Migration 文件的时间戳前缀 |

---

## API 文档

当 `config/project.php` 中 `showDoc` 为 `true` 时，自动注册以下路由：

| 方法 | URI | 说明 |
|------|-----|------|
| GET | `/api/docs/openapi` | 返回 OpenAPI 3.1 JSON 规范 |
| GET | `/api/docs/plantuml?name=xxx` | 返回 PlantUML ER 图 |

访问 `/docs/index.html` 可查看内置的 API 文档前端页面。

---

## Builder 宏

在 Model 中 use `ModelTrait` 即可获得 IDE 自动补全支持：

```php
use LaravelDev\App\Traits\ModelTrait;

class User extends Model
{
    use ModelTrait;
}
```

### 可用宏

```php
// 条件查询（参数中存在时才生效）
User::query()
    ->ifWhere($params, 'status', 'status')              // 等值查询
    ->ifWhereLike($params, 'name', 'name')               // LIKE 模糊查询
    ->ifWhereLikeKeyword($params, 'keyword', ['name', 'email']) // 多字段关键词搜索
    ->ifWhereNumberRange($params, 'amount', 'amount')    // 数值范围
    ->ifWhereDateRange($params, 'created_at', 'created_at')     // 日期范围
    ->ifIsNull($params, 'is_deleted', 'deleted_at')      // NULL 判断
    ->order($params['sorter'] ?? null, 'id')              // Ant Design 排序
    ->page();                                             // 自动分页

// 关联查询
User::query()->ifHasWhereLike($params, 'role_name', 'roles', 'name');

// ID 查询（支持锁行）
User::query()->getById($id, throw: true, lock: true);

// 下拉选项
User::query()->forSelect('id', 'name', 'created_at');

// 唯一性校验（重复则抛异常）
User::query()->unique($params, ['code'], '编号', 'code');
```

---

## Traits

### ControllerTrait

```php
use LaravelDev\App\Traits\ControllerTrait;

class UserController extends Controller
{
    use ControllerTrait;

    public function store(Request $request)
    {
        $params = $request->validate([...]);

        $this->crypto($params, 'password');   // 自动 bcrypt 加密

        $perPage = $this->perPage();           // 获取分页大小（已校验）
    }
}
```

### EnumTrait

```php
use LaravelDev\App\Traits\EnumTrait;

enum UserType: string
{
    use EnumTrait;

    case Admin = 'admin';
    case User = 'user';
}

// 使用
UserType::Values();                    // ['admin', 'user']
UserType::GetLabels();                 // ['Admin', 'User']
UserType::GetLabelByValue('admin');    // 'Admin'
UserType::IsValueInEnum('admin');      // true
UserType::GetMaxLength();              // 5
UserType::Comment('用户类型');         // '用户类型:UserType'
```

### TestCaseTrait

```php
use LaravelDev\App\Traits\TestCaseTrait;

class UserTest extends TestCase
{
    use TestCaseTrait;

    public function testStore()
    {
        // 自动从方法名解析 URI
        $this->go('POST', ['name' => 'test']);
    }
}
```

---

## 异常处理

### 全局错误抛出

```php
use function LaravelDev\ee;

// 简单用法
ee('操作失败');

// 完整用法
ee('余额不足', 30001, '请充值后重试', 'notification', 400);
```

### 统一 JSON 响应

`JsonWrapperMiddleware` 自动包装所有 API 响应为统一格式：

```json
{
  "success": true,
  "data": {}
}
```

错误响应：

```json
{
  "success": false,
  "message": "错误描述",
  "code": 10001,
  "description": "详细信息"
}
```

---

## 辅助工具

### SchemaHelper —— Migration 辅助

```php
use LaravelDev\App\Helpers\SchemaHelper;

Schema::create('orders', function (Blueprint $table) {
    SchemaHelper::Enum($table, 'status', OrderStatusEnum::class, '订单状态');
    SchemaHelper::ForeignId($table, 'user_id', '用户ID', 'users');
    SchemaHelper::Cities($table, 'receiver');  // 省/市/区/街道字段组
});
```

### FastExcelHelper —— Excel 导入导出

```php
use LaravelDev\App\Helpers\FastExcelHelper;

// 导出（流式，内存高效）
FastExcelHelper::Export(User::query(), '用户列表', fn($item) => [
    '姓名' => $item->name,
    '邮箱' => $item->email,
]);

// 导入（自动事务，失败回滚）
FastExcelHelper::Import($file, function ($row) {
    User::create($row);
});
```

### AwsS3Helper —— S3 文件上传

```php
use LaravelDev\App\Helpers\AwsS3Helper;

// 预签名上传
$url = AwsS3Helper::PreUpload('avatars', 'photo.jpg');

// 生成临时访问 URL
$url = AwsS3Helper::TemporaryUrl('avatars/ulid.jpg', 30);

// 富文本中 Base64 图片替换为 S3 URL
AwsS3Helper::ReplaceImageToOss($params, ['content'], 'editor', 800, 80);

// 生成缩略图
AwsS3Helper::CreateThumbUrl($images, 200, 80);
```

---

## 约定

### 路由自动发现

放置在 `App\Modules\{Module}\{SubModule}\{Name}Controller` 的 Controller 会被自动发现并注册路由：

```
App\Modules\Admin\Users\UserController
  → POST /admin/users/user/store
  → POST /admin/users/user/update
  → POST /admin/users/user/delete
  → ...
```

通过 DocBlock 注解控制路由行为：

```php
/**
 * @intro 创建用户
 * @methods POST
 * @responseJson {"id": 1, "name": "test"}
 */
public function store(Request $request): JsonResponse
{
    // ...
}
```

支持的注解：`@intro`、`@methods`、`@responseJson`、`@responseBody`、`@skipInRouter`、`@withMiddlewares`、`@withoutMiddlewares`

### 数据库注释约定

在 Migration 中通过特殊注释语法传递元数据：

```php
$table->string('password')->comment('密码 [hidden]');           // 标记为隐藏字段
$table->string('type')->comment('类型 [enum:UserTypeEnum]');     // 关联枚举类
$table->unsignedBigInteger('user_id')->comment('用户ID [ref:users]'); // 标记外键
```

---

## 依赖

| 包名 | 用途 |
|------|------|
| `mews/captcha` | 验证码 |
| `mews/purifier` | HTML 净化 |
| `orangehill/iseed` | 数据库导出为 Seed |
| `kalnoy/nestedset` | 嵌套集合（树形结构） |
| `league/flysystem-aws-s3-v3` | AWS S3 文件存储 |
| `rap2hpoutre/fast-excel` | Excel 导入导出 |
| `spatie/laravel-permission` | 角色权限管理 |
| `spatie/eloquent-sortable` | 模型排序 |
| `spatie/laravel-tags` | 标签管理 |
| `vinkla/hashids` | ID 哈希混淆 |
| `jawira/plantuml-encoding` | PlantUML 编码 |

## 环境要求

- PHP >= 8.3
- Laravel >= 13.0

## License

[MIT](LICENSE)
