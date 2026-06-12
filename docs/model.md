# Model 生成（三层继承模式）

`gd` 命令为每张表生成两个 Model 文件，结合项目自建的 Base 基类，形成三层继承结构：

```
Illuminate\Database\Eloquent\Model        ← Laravel 框架
    └── App\Models\Base\Base               ← 项目基类（手动创建，全局共享逻辑）
        └── App\Models\Base\BaseUser       ← 自动生成，可覆盖（gd -f）
            └── App\Models\User             ← 自动生成一次，永不覆盖
```

## 第一层：Base 基类（项目手动创建）

> **前置要求**：使用 `gd` 生成 Model 之前，项目需自行创建此文件。

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

## 第二层：Base Model（自动生成，可覆盖）

位于 `app/Models/Base/BaseUser.php`，包含从数据库反射出来的所有属性：

```php
<?php
// app/Models/Base/BaseUser.php
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
class BaseUser extends Base
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

## 第三层：User Model（生成一次，永不覆盖）

位于 `app/Models/User.php`，空类继承 BaseUser，你在这里写业务逻辑：

```php
<?php
// app/Models/User.php
namespace App\Models;

use App\Models\Base\BaseUser;

class User extends BaseUser
{
    // 在这里添加你的自定义方法、作用域、访问器等
    // 即使重新执行 gd -f users，这个文件也不会被覆盖
}
```

## 为什么这样设计？

数据库结构会变，你需要反复执行 `gd -f` 更新 Base Model。三层设计确保：

| 层级 | 谁维护 | 会被覆盖吗 | 写什么 |
|------|--------|-----------|--------|
| `Base` | 你手动创建 | ❌ 永远不会 | 全局 scope、共享 `@method` |
| `BaseUser` | `gd` 命令生成 | ✅ `gd -f` 会覆盖 | 表结构映射（`$fillable`、`casts`、关联方法） |
| `User` | `gd` 命令生成一次 | ❌ 永远不会 | 你的业务逻辑（自定义方法、作用域等） |

## 命令

```bash
# 为 users 表生成 Model
php artisan gd users

# 强制覆盖 Base Model（User Model 不会被覆盖）
php artisan gd users -f

# 批量为所有表生成 Model（跳过 config 中 dbSkipGenModel 的表）
php artisan gam
```

## 自动识别的表特征

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
