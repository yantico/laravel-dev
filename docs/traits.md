# Traits 工具集

## ControllerTrait —— 控制器通用方法

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

## EnumTrait —— 枚举增强

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

## ModelTrait —— IDE 自动补全

空 trait，仅提供 PHPDoc `@method` 注解，让 IDE 自动补全 Builder 宏方法：

```php
class User extends BaseUser
{
    use ModelTrait; // 加上这个，IDE 就能自动补全 ifWhere、page、getById 等方法
}
```

## TestCaseTrait —— 测试辅助

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
