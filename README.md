# Laravel Dev

<p align="center">
<strong>Laravel 12/13 快速开发工具包</strong>
</p>

<p align="center">
基于 <strong>约定优于配置</strong> 的理念，通过数据库表结构自动生成 Model、Controller、Migration、Enum、Test，<br>
解析 Controller 源码自动产出 OpenAPI 文档和 ER 图，内置 Builder 宏、统一异常处理、权限校验等常用能力。
</p>

<p align="center">
<a href="https://packagist.org/packages/yantico/laravel-dev"><img src="https://img.shields.io/packagist/v/yantico/laravel-dev.svg?style=flat-square" alt="Latest Version on Packagist"></a>
<a href="https://packagist.org/packages/yantico/laravel-dev"><img src="https://img.shields.io/packagist/dt/yantico/laravel-dev.svg?style=flat-square" alt="Total Downloads"></a>
<img src="https://img.shields.io/badge/PHP-%5E8.2-777BB4?style=flat-square" alt="PHP Version">
<img src="https://img.shields.io/badge/Laravel-%5E12.0%20%7C%20%5E13.0-FF2D20?style=flat-square" alt="Laravel Version">
<img src="https://img.shields.io/badge/license-MIT-green?style=flat-square" alt="License">
</p>

---

## 目录

- [核心能力](#核心能力)
- [环境要求](#环境要求)
- [安装](#安装)
- [5 分钟快速上手](#5-分钟快速上手)
- [项目配置详解](#项目配置详解)
- [详细文档 → docs/](#详细文档)

---

## 核心能力

| 能力 | 说明 |
|------|------|
| **代码生成** | 从数据库表结构一键生成 Model（Base + 可编辑双层）、Controller（CRUD）、Enum、Migration、Test |
| **路由自动发现** | Controller 放到 `App\Modules\` 即自动注册路由，无需手动写 `routes/*.php` |
| **OpenAPI 文档** | 解析 Controller 源码中的 `request()->validate()` 和 DocBlock 注解，零配置产出 OpenAPI 3.1 规范 |
| **ER 图** | 基于 PlantUML 自动生成数据库表关系图，按业务分组 |
| **Builder 宏** | `ifWhere`、`ifWhereLike`、`page`、`order` 等条件链式查询，告别重复的 `if ($params['xxx'])` 判断 |
| **统一响应** | `JsonWrapperMiddleware` 自动包装 `{success, data}` 格式，分页自动加 `meta` |
| **异常处理** | `ee()` 全局抛异常 + `ExceptionRender` 统一渲染 JSON 错误响应 |

---

## 环境要求

| 依赖 | 版本 |
|------|------|
| PHP | >= 8.2 |
| Laravel | >= 12.0 或 >= 13.0 |

---

## 安装

```bash
composer require yantico/laravel-dev
```

发布配置文件和 API 文档前端资源到项目中：

```bash
php artisan vendor:publish --tag=laravel-dev
```

发布后会在你的项目中生成：

```
config/project.php     ← 包配置文件（可自定义）
public/docs/            ← API 在线文档前端页面
```

---

## 5 分钟快速上手

假设你已经有一个正在运行的 Laravel 项目，并且数据库中有一张 `users` 表。

### 第 1 步：编辑项目基础文件

安装后需要编辑 6 个基础文件（新建 1 个 + 编辑 5 个），详见 [项目基本配置 →](docs/setup.md)：

| 文件 | 说明 |
|------|------|
| `app/Models/Base/Base.php` | **新建** — Model 全局基类，引入 ModelTrait |
| `app/Http/Controllers/Controller.php` | 编辑 — 引入 ControllerTrait |
| `bootstrap/app.php` | 编辑 — 中间件组 + 异常处理（**最关键**） |
| `routes/api.php` | 编辑 — 调用 RouterServices::Register() |
| `tests/TestCase.php` | 编辑 — 引入 TestCaseTrait |
| `.env` | 编辑 — 时区、语言、日志、数据库 |

### 第 2 步：配置 `config/project.php`

发布后编辑配置文件，按需调整（大部分保持默认即可）：

```php
return [
    'showDoc' => env('SHOW_DOC', true),  // 生产环境建议设为 false
    'perPageAllow' => [10, 20, 50, 100],
];
```

### 第 3 步：构建缓存

工具包通过反射数据库和代码来工作，首次使用前需要构建缓存：

```bash
php artisan cdb    # 缓存数据库表结构（本地环境会自动刷新，无需重复执行）
```

### 第 4 步：生成代码

```bash
# 为 users 表生成 Model（Base + 可编辑类）
php artisan gd users

# 生成 Controller（放在 Admin 模块下）
php artisan gc Admin/Users

# 生成 Enum（如 users 表有个 type 字段）
php artisan ge users/type
```

### 第 5 步：查看 API 文档

启动项目后访问：

```
http://your-app.test/docs/index.html
```

前端会自动从 `/api/docs/openapi` 获取 OpenAPI 规范并渲染。你可以看到所有接口的参数、响应示例和数据库表结构。

**就这样！** 一个完整的 CRUD 接口 + 在线文档就绪了。

---

## 项目配置详解

`config/project.php` 是本工具包的核心配置，控制代码生成行为、文档展示、权限校验等：

```php
return [
    // ===== 分页 =====
    // 允许的分页大小，用于 Builder 宏 page() 和 ControllerTrait::perPage()
    'perPageAllow' => [10, 20, 50, 100],

    // ===== 数据库备份 =====
    // php artisan db:backup 会把这些表的数据导出为 Seed 文件
    'dbBackupList' => [
        'sys_permissions',
        'sys_roles',
        'personal_access_tokens',
    ],

    // ===== Migration 重命名 =====
    // php artisan Rename 时跳过匹配这些模式的文件
    'migrationBlacklists' => [],

    // ===== Model 生成 =====
    // gam 命令跳过这些表（不生成 Model）
    'dbSkipGenModel' => ['cache', 'sessions', 'jobs', 'failed_jobs'],

    // 自动为这些表的 Model 添加 HasApiTokens trait
    'hasApiTokens' => ['admins', 'wechats'],

    // 自动为这些表的 Model 添加 HasRoles trait
    'hasRoles' => ['sys_permissions', 'sys_roles'],

    // 自动为这些表的 Model 添加 Kalnoy\NodeTrait（嵌套集合/树形结构）
    'hasNodeTrait' => ['categories'],

    // ===== 在线文档 =====
    // 是否注册文档路由（/api/docs/openapi、/api/docs/plantuml）
    'showDoc' => env('SHOW_DOC', true),

    // ===== 权限校验 =====
    // 这些模块下的接口会启用 CheckPermissionMiddleware
    'enableCheckPermissionModules' => ['Admin'],

    // ===== ER 图 =====
    // PlantUML 渲染服务器地址
    'plantUmlServer' => 'https://www.plantuml.com/plantuml/svg/',

    // ER 图分组（每组包含一组相关联的表）
    'erMaps' => [
        '用户体系' => ['users', 'user_profiles', 'user_logs'],
        '订单系统' => ['orders', 'order_items', 'products'],
    ],
];
```

> 各配置项的详细说明见 [项目基本配置](docs/setup.md)。

---

## 详细文档

| 文档 | 说明 |
|------|------|
| [项目基本配置](docs/setup.md) | 安装后必做的 6 个基础文件配置（**入门必读**） |
| [Artisan 命令大全](docs/commands.md) | 缓存、代码生成、调试、工具命令 |
| [路由自动发现](docs/routing.md) | 目录约定、Controller 继承链、路由注册规则 |
| [Model 生成](docs/model.md) | 三层继承模式（Base → BaseModel → Model） |
| [代码生成](docs/code-generation.md) | Controller、Enum、Migration、Test 生成 |
| [Builder 宏方法](docs/builder-macros.md) | 条件查询、分页、排序等 13 个宏 |
| [Traits 工具集](docs/traits.md) | ControllerTrait、EnumTrait、ModelTrait、TestCaseTrait |
| [中间件](docs/middleware.md) | JsonWrapperMiddleware、CheckPermissionMiddleware、完整中间件组配置 |
| [辅助工具类](docs/helpers.md) | SchemaHelper、FastExcelHelper、AwsS3Helper 等 |
| [异常处理](docs/exceptions.md) | ee() 全局函数、ExceptionRender 统一渲染 |
| [API 在线文档](docs/api-docs.md) | OpenAPI 文档配置和使用 |
| [约定](docs/conventions.md) | 数据库注释约定、DocBlock 注解约定 |
| [Docker 容器化部署](docs/docker.md) | Supervisor 管理定时任务和队列 |
| [完整开发流程](docs/workflow.md) | 从零到一开发新模块的推荐工作流 |
| [依赖说明](docs/dependencies.md) | 内置的 Laravel 生态包列表 |

## License

[MIT](LICENSE)
