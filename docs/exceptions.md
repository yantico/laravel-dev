# 异常处理

## ee() —— 全局异常抛出

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

## ExceptionRender —— 统一异常渲染

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
