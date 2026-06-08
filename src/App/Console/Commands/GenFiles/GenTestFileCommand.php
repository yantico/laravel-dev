<?php

namespace LaravelDev\App\Console\Commands\GenFiles;

use Illuminate\Support\Str;
use LaravelDev\App\Console\Commands\BaseCommand;
use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Services\RouterServices;
use ReflectionException;

class GenTestFileCommand extends BaseCommand
{
    protected $name = 'gt';
    protected $description = "根据输入的路径，生成控制器的测试文件，包含所有方案和请求参数。路径通过斜杠/拆分成[模块名]和[表名]。
    模块名：会转成大写开头的驼峰，斜杠/分割成数组，支持多级目录；
    表名：会转成大写开头的驼峰；
    例如：php artisan gt admin/users
    例如：php artisan gt Admin/auth/CompanyAdmin";

    /**
     * @return int
     * @throws Err
     * @throws ReflectionException
     */
    public function handle(): int
    {
        list($name, $force) = $this->getNameAndForce();

        $modulesName = Str::of($name)->explode('/')->map(function ($item) {
            return Str::of($item)->replace('Controller', '')->studly()->toString();
        });

        $modelName = $modulesName->pop();

        $testNamespace = "Tests\\Modules\\" . $modulesName->implode('\\');
        $appNamespace = "App\\Modules\\" . $modulesName->implode('\\');

        $testClassName = $modelName . 'ControllerTest';
        $appFullClassName = "$appNamespace\\{$modelName}Controller";

//        dd($modulesName, $modelName, $testNamespace, $appNamespace, $testClassName, $appFullClassName);

        $replaces = [
            '{{ namespace }}' => $testNamespace,
            '{{ appFullClassName }}' => $appFullClassName,
            '{{ modelName }}' => $modelName,
            '{{ functions }}' => $this->getFunctions($appFullClassName),
        ];

        $this->GenFile('test.stub', $replaces, $testNamespace, $testClassName, $force);

        return self::SUCCESS;
    }

    /**
     * @param $fullClassName
     * @return string
     * @throws Err
     * @throws ReflectionException
     */
    private function getFunctions($fullClassName): string
    {
        $router = RouterServices::GetRouterModelByFullClassName($fullClassName);
        $stringBuilder = [];
        foreach ($router->actions as $action) {

            $params = [];
            foreach ($action->requestBody as $param) {
                $params[] = "'$param->name' => '', # $param->description";
            }
            $paramsStr = implode(",\n\t\t\t", $params);
            $functionName = 'test' . Str::of($action->uri)->camel()->ucfirst();
            $stringBuilder[] = "public function $functionName()
    {
        \$this->go(__METHOD__, [
            $paramsStr
        ]);
    }";
        }
        return implode("\n\n\t", $stringBuilder);
    }
}
