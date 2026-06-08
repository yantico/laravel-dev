<?php

namespace LaravelDev\App\Console\Commands\GenFiles;

use Illuminate\Support\Str;
use LaravelDev\App\Console\Commands\BaseCommand;
use LaravelDev\App\Exceptions\Err;

class GenEnumFileCommand extends BaseCommand
{
    protected $name = 'ge';
    protected $description = "根据输入的名称，生成enum文件。
    如果：名称中存在/，则根据/分割成【表名】/【字段名】，转成大写驼峰，再生成文件。
    例如：php artisan ge users/type => UsersTypeEnum

    如果：名称中不存在/，则直接根据名称生成文件。
    例如：php artisan ge UsersTypeEnum";

    /**
     * @return int
     * @throws Err
     */
    public function handle(): int
    {
        list($name, $force) = $this->getNameAndForce();

        list($className, $field) = $this->getClassNameAndField($name);
        $namespace = 'App\\Enums';
        $replaces = [
            '{{ comment }}' => $className,
            '{{ field }}' => $field,
            '{{ className }}' => $className,
        ];
        $this->GenFile('enum.stub', $replaces, $namespace, $className, $force);

        return self::SUCCESS;
    }

    /**
     * @param mixed $name
     * @return array
     */
    private function getClassNameAndField(mixed $name): array
    {
        if (Str::of($name)->contains('/')) {
            $arr = Str::of($name)->explode('/');
            $field = $arr->last();
            if (strtolower($field) != 'enum')
                $arr->push('enum');
            $className = $arr->map(function ($item) {
                return Str::of($item)->studly();
            })->implode('');
            return [$className, $field];
        } else {
            $name = Str::of($name)->studly();
            if (!$name->endsWith('Enum'))
                $name = $name->append('Enum');
            return [$name->toString(), null];
        }
    }
}
