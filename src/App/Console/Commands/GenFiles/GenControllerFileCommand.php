<?php

namespace LaravelDev\App\Console\Commands\GenFiles;

use Illuminate\Support\Str;
use LaravelDev\App\Console\Commands\BaseCommand;
use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Services\DBServices;

class GenControllerFileCommand extends BaseCommand
{
    protected $name = 'gc';
    protected $description = "根据输入的路径，生成控制器文件。路径通过斜杠/拆分成[模块名]和[表名]。
    模块名：会转成大写开头的驼峰，斜杠/分割成数组，支持多级目录；
    表名：转蛇形；
    例如：php artisan gc admin/users
    例如：php artisan gc Admin/auth/CompanyAdmins";

    /**
     * @return int
     * @throws Err
     */
    public function handle(): int
    {
        list($name, $force) = $this->getNameAndForce();

        $modulesName = Str::of($name)->explode('/')->map(function ($item) {
            return Str::of($item)->studly()->toString();
        });

        $tableName = Str::of($modulesName->pop())->snake(); //->singular()->plural();
        $table = DBServices::GetTable($tableName);

        $namespace = "App\\Modules\\" . $modulesName->implode('\\');
        $className = $table->modelName . 'Controller';

        $replaces = [
            '{{ namespace }}' => $namespace,
            '{{ modelName }}' => $table->modelName,
            '{{ comment }}' => $table->comment,
            '{{ validateString }}' => implode("\n\t\t\t", $table->getValidates()),
        ];
        $this->GenFile($table->hasSoftDelete ? 'controller.softDelete.stub' : 'controller.stub', $replaces, $namespace, $className, $force);
        return self::SUCCESS;
    }
}
