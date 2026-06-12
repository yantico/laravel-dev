# 完整开发流程

以下是从零开始一个新功能模块的推荐工作流：

## 场景：新增一个「商品管理」模块

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
