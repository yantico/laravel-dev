<?php

namespace LaravelDev\App\Console\Commands\GenFiles;

use Illuminate\Support\Str;
use LaravelDev\App\Console\Commands\BaseCommand;
use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Services\DBServices;

class GenModelFilesCommand extends BaseCommand
{
    protected $name = 'gd';
    protected $description = "根据输入的数据库表名，生成模型文件。
    表名：会转成蛇形，单数，复数。
    例如：php artisan gd users -f
    例如：php artisan gd User";

    /**
     * @return int
     * @throws Err
     */
    public function handle(): int
    {
        list($name, $force) = $this->getNameAndForce();

        $tableName = str()->of($name)->snake()->singular()->plural();
        $table = DBServices::GetTable($tableName);

        $namespace = "App\\Models\\Base";
        $className = "Base" . $table->modelName;
        $replaces = [
            '{{ namespace }}' => $namespace,
            '{{ properties }}' => $table->getProperties(),
            '{{ importClasses }}' => $table->getImportClasses(),
            '{{ className }}' => $className,
            '{{ traits }}' => $table->getTraits(),
            '{{ tableName }}' => $table->name,
            '{{ comment }}' => $table->comment,
            '{{ fillable }}' => $table->getFillAble(),
            '{{ hidden }}' => $table->getHidden(),
//            '{{ guard_name }}' => $table->guardName ? "protected string \$guard_name = '$table->guardName';" : '',
            '{{ casts }}' => $table->getCasts(),
            '{{ relations }}' => $table->getRelations(),
        ];
        $this->GenFile('model.base.stub', $replaces, $namespace, $className, $force);

        $namespace = "App\\Models";
        $className = Str::of($tableName)->camel()->ucfirst();
        $replaces = [
            '{{ className }}' => $className,
        ];
        $this->GenFile('model.stub', $replaces, $namespace, $className, false);

        return self::SUCCESS;
    }
}
