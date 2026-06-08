<?php

return [
    # 分页的时候，每页显示的数量范围
    'perPageAllow' => [10, 20, 50, 100],

    # 通过 php artisan db:backup 批量数据库备份的表的清单
    # 会生成到 database/seeds 目录下
    'dbBackupList' => [
        'sys_permissions',
        'sys_roles',
        'sys_role_has_permissions',
        'sys_model_has_roles',
        'personal_access_tokens',
    ],

    # 当运行 php artisan Rename 的时候，会统一把migration文件重命名
    # 重命名以后排序会更好看一点，同一个模块的表会在一起
    # 当然系统中不需要重命名的表，可以在这里配置
    'migrationBlacklists' => [
        'create_users_table',
        'create_cache_table',
        'create_jobs_table',
    ],

    # 当运行 php artisan gam 自动根据数据库所有表，批量生成全部模型文件。
    # 如果不需要生成模型的表，可以在这里配置
    'dbSkipGenModel' => [
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'migrations',
        'password_reset_tokens',
        'sessions',
        'personal_access_tokens',
        'users',
    ],

    # 在生成模型文件的时候，表名如果在以下配置中，会自动加上 HasApiTokens trait
    'hasApiTokens' => ['admins', 'wechats'],

    # 在生成模型文件的时候，表名如果在以下配置中，会自动加上 HasRoles trait
    'hasRoles' => ['sys_permissions', 'sys_roles', 'company_admins', 'admins'],

    # 在生成模型文件的时候，表名如果在以下配置中，会自动加上 HasNode trait
    'hasNodeTrait' => ['sys_permissions'],

    # 保留字段，暂时没有用到
    # 为了适应达梦数据库，增加了一个字段，用来存储表的前缀
    'tablePrefix' => '',

    # 是否显示文档
    # 在 php artisan serve 后，可以通过 http://localhost:8000/docs 访问项目文档
    'showDoc' => env('SHOW_DOC', true),

    # 启用接口权限检查的模块列表
    'enableCheckPermissionModules' => ['Admin'],

    # 在文档系统中，浏览ER图的时候，需要填写plantuml的渲染接口
    'plantUmlServer' => env('PLANT_UML_SERVER', 'https://www.plantuml.com/plantuml/svg/'),

    # 在文档系统中，浏览ER图的时候，会根据这个配置，把表分组，分别生成子图
    'erMaps' => [
        '标准' => [
            'admins',
            'sys_permissions',
            'sys_roles',
            'sys_role_has_permissions',
            'sys_model_has_roles',
            'personal_access_tokens',
        ],
    ]
];
