# Artisan 命令大全

## 缓存命令

这些命令将数据库/代码的元数据缓存到 `storage/framework/cache/`，供其他命令和在线文档使用。

> **本地环境**：缓存会自动刷新，无需手动执行。**生产环境**：部署后需手动执行一次。

| 命令 | 说明 | 何时执行 |
|------|------|----------|
| `php artisan cdb` | 反射所有数据库表结构，缓存为元数据 | 数据库结构变更后 |
| `php artisan ce` | 反射 `app/Enums/` 下所有枚举类，缓存元数据 | 新增/修改枚举后 |
| `php artisan cr` | 扫描 `app/Modules/` 下所有 Controller，缓存路由元数据 | 新增/修改 Controller 后 |

## 代码生成命令

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

## 调试命令

用于查看元数据和生成代码模板，不会写入任何文件。

| 命令 | 参数格式 | 说明 |
|------|----------|------|
| `php artisan ddb {name}` | 表名，如 `User` 或 `users` | 输出表的完整元数据 JSON（列、类型、关系等） |
| `php artisan de {name}` | 枚举类名，如 `UserTypeEnum` | 输出枚举的元数据 JSON |
| `php artisan dr {name}` | 模块路径，如 `Admin/Users` | 输出 Controller 的路由元数据 |
| `php artisan dt {name}` | 表名，如 `users` | 输出代码模板片段（`$fillable`、验证规则、插入模板），方便复制粘贴 |

## 工具命令

| 命令 | 说明 |
|------|------|
| `php artisan db:backup` | 将 `config('project.dbBackupList')` 中的表数据导出为 Seed 文件（基于 `iseed`） |
| `php artisan Rename` | 重命名 `database/migrations/` 中的文件，统一日期前缀便于排序 |
