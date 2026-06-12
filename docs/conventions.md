# 约定

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
