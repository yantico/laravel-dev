# API 在线文档

## 开启/关闭

```php
// config/project.php
'showDoc' => env('SHOW_DOC', true),  // 生产环境设为 false
```

## 访问地址

```
http://your-app.test/docs/index.html
```

## 后端路由

| 方法 | URI | 说明 |
|------|-----|------|
| GET | `/api/docs/openapi` | 返回完整 OpenAPI 3.1 JSON（含接口、数据库表结构、枚举定义） |
| GET | `/api/docs/plantuml?name=xxx` | 返回指定分组的 PlantUML ER 图 |

## 文档内容来源

在线文档包含三部分信息：

1. **API 接口** —— 来自 `cr` 缓存，解析 Controller 源码中的 `request()->validate()` 和 DocBlock 注解
2. **数据库表结构** —— 来自 `cdb` 缓存，展示每张表的列、类型、注释
3. **枚举定义** —— 来自 `ce` 缓存，展示枚举值、标签、颜色
