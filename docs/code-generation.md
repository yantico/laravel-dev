# 代码生成（Controller / Enum / Migration / Test）

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
<?php
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
