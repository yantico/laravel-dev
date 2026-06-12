# 中间件

## JsonWrapperMiddleware —— 统一响应包装

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
