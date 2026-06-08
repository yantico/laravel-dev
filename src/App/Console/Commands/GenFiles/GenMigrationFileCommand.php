<?php

namespace LaravelDev\App\Console\Commands\GenFiles;


use LaravelDev\App\Console\Commands\BaseCommand;
use LaravelDev\App\Exceptions\Err;

class GenMigrationFileCommand extends BaseCommand
{
    protected $name = 'gm';
    protected $description = "根据输入的数据库表名，生成migration迁移文件。
    表名：会转成蛇形，单数，复数。
    例如：php artisan gm users
    例如：php artisan gm User";

    /**
     * @return int
     * @throws Err
     */
    public function handle(): int
    {
        list($name,) = $this->getNameAndForce();

        $tableName = str()->of($name)->snake()->singular()->plural();

        $this->call('make:migration', [
            'name' => "create_{$tableName}_table",
            '--create' => $tableName,
            '--table' => $tableName,
        ]);

        return self::SUCCESS;
    }
}
