<?php

namespace LaravelDev\App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Helpers\DocBlockReader;
use LaravelDev\App\Models\Router\RouterActionModel;
use LaravelDev\App\Models\Router\RouterControllerModel;
use LaravelDev\App\Models\Router\RouterParamModel;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class RouterServices
{
    /**
     * @return void
     * @throws Err
     * @throws ReflectionException
     */
    public static function Cache(): void
    {
        Cache::store('file')->put('_dev_router', self::ReflectRoutersToModel());
        Cache::store('file')->put('_dev_router_select', self::GetApiSelect());
    }

    /**
     * @return RouterControllerModel[]
     * @throws Err
     * @throws ReflectionException
     */
    public static function GetFromCache(): array
    {
        if (App::environment('local')) {
            return self::ReflectRoutersToModel();
        } else {
            return Cache::store('file')->rememberForever('_dev_router', function () {
                logger()->debug('RouterServices::GetFromCache... cache missed');
                return self::ReflectRoutersToModel();
            });
        }
    }

    /**
     * @return RouterControllerModel[]
     * @throws Err
     * @throws ReflectionException
     */
    public static function GetApiSelectFromCache(): array
    {
        if (App::environment('local')) {
            return self::GetApiSelect();
        } else {
            return Cache::store('file')->rememberForever('_dev_router_select', function () {
                logger()->debug('RouterServices::GetApiSelectFromCache... cache missed');
                return self::GetApiSelect();
            });
        }
    }

    /**
     * @return array
     * @throws Err
     * @throws ReflectionException
     */
    protected static function GetApiSelect(): array
    {
        $routers = self::GetFromCache();
        $arr = [];
        foreach ($routers as $router) {
            foreach ($router->actions as $action) {
                if ($action->skipInRouter)
                    continue;
                $arr[] = $action->fullUri;
            }
        }
        return self::buildNestedArray($arr);
    }

    /**
     * @param string $className
     * @return RouterControllerModel
     * @throws Err
     * @throws ReflectionException
     */
    public static function GetRouterModelByFullClassName(string $className): RouterControllerModel
    {
        $routers = self::GetFromCache();
        $filteredObject = array_filter($routers, function ($router) use ($className) {
            return $router->fullClassName === $className;
        });
        return reset($filteredObject);
    }

    /**
     * @return RouterControllerModel[]
     * @throws Err
     * @throws ReflectionException
     */
    public static function ReflectRoutersToModel(): array
    {
        $files = File::allFiles(app_path('Modules'));
        $routers = [];

        $enableCheckPermissionModules = config('project.enableCheckPermissionModules', []);

        foreach ($files as $file) {
            if ($file->getFilename() == 'BaseController.php')
                continue;

            $controllerModel = new RouterControllerModel();

            $moduleNames = explode(DIRECTORY_SEPARATOR, $file->getRelativePathname());
            array_pop($moduleNames);

            $namespace = 'App\\Modules\\' . implode('\\', $moduleNames);
            $className = str_replace('.php', '', $file->getFilename());
            $fullClassName = $namespace . '\\' . $className;
            $name = str_replace('Controller.php', '', $file->getFilename());

            $ctrlRef = new ReflectionClass($fullClassName);
            $ctrlDoc = DocBlockReader::parse($ctrlRef->getDocComment());

            $controllerModel->namespace = $namespace;
            $controllerModel->className = $className;
            $controllerModel->fullClassName = $fullClassName;
            $controllerModel->name = $name;
            $controllerModel->moduleNames = $moduleNames;
            $controllerModel->intro = $ctrlDoc['intro'] ?? '';
            $controllerModel->routerPrefix = collect([...$moduleNames, $name])->map(function ($item) {
                return Str::of($item)->snake()->toString();
            })->implode('/');
            $controllerModel->enableCheckPermission = in_array($moduleNames[0], $enableCheckPermissionModules);

            foreach ($ctrlRef->getMethods() as $methodRef) {
                if ($methodRef->class != $controllerModel->fullClassName || $methodRef->getModifiers() !== 1 || $methodRef->isConstructor())
                    continue;
                $actionDoc = DocBlockReader::parse($methodRef->getDocComment());
//                if ($methodRef->name === 'show')
//                    dd($actionDoc);
                $action = new RouterActionModel();
                $action->intro = $actionDoc['intro'] ?? '';
                $action->functionName = $methodRef->getName();
                $action->uri = Str::of($methodRef->getName())->snake()->toString();
                $action->fullUri = $controllerModel->routerPrefix . '/' . $action->uri;
                $action->fullName = str_replace('/', '.', $controllerModel->routerPrefix) . ".$action->uri";
                $action->methods = ($actionDoc['methods'] ?? false) ? explode(',', $actionDoc['methods']) : ['POST'];
                $action->withMiddlewares = ($actionDoc['withMiddlewares'] ?? false) ? explode(',', $actionDoc['withMiddlewares']) : null;
                $action->withoutMiddlewares = ($actionDoc['withoutMiddlewares'] ?? false) ? explode(',', $actionDoc['withoutMiddlewares']) : null;
                $action->skipInRouter = $actionDoc['skipInRouter'] ?? false;
                $action->requestBody = self::getRequestBody($methodRef);
                $action->responseJson = $actionDoc['responseJson'] ?? null;
                $action->responseBody = $actionDoc['responseBody'] ?? null;
                $action->isDownload = str_contains($actionDoc['return'] ?? false, 'StreamedResponse');

                $controllerModel->actions[] = $action;
            }

            $routers[] = $controllerModel;
        }
        return $routers;
    }

    /**
     * @return void
     * @throws Err
     * @throws ReflectionException
     */
    public static function Register(): void
    {
        foreach (self::GetFromCache() as $api) {
//            $arr = [...$api->moduleNames, $api->name];
            Route::prefix($api->routerPrefix)->middleware($api->moduleNames[0] ?? null)->group(function ($router) use ($api) {
                foreach ($api->actions as $action) {
                    if ($action->skipInRouter)
                        continue;

//                    $middlewares = [];
//                    $action->skipAuth ?: $middlewares[] = 'auth:' . $api->moduleNames[0];
//                    $action->skipWrap ?: $middlewares[] = JsonWrapperMiddleware::class;
//                    $action->skipPermission ?: $middlewares[] = CheckPermissionMiddleware::class;

                    $router->match(
                        $action->methods,
                        $action->uri,
                        [$api->fullClassName, $action->functionName]
                    )
                        ->name(str_replace('/', '.', $api->routerPrefix) . ".$action->uri")
                        ->withoutMiddleware($action->withoutMiddlewares);
                }
            });
        }
    }

    /**
     * @param ReflectionMethod $methodRef
     * @return array
     * @throws Err
     */
    private static function getRequestBody(ReflectionMethod $methodRef): array
    {
        // 获得方法的源代码
        $startLine = $methodRef->getStartLine();
        $endLine = $methodRef->getEndLine();
        $length = $endLine - $startLine;
        $source = file($methodRef->getFileName());
        $lines = array_slice($source, $startLine, $length);

        // 解析每一行
        $strStart = ']);';
        $strEnd1 = '$params = $request->validate([';
        $strEnd2 = '$params = request()->validate([';
        $start = $end = false;
        $arr1 = [];
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t == $strStart) $end = true;
            if ($start && !$end)
                $arr1[] = $t;
            if ($t == $strEnd1 || $t == $strEnd2) $start = true;
        }

        // 解析参数
        $arr2 = [];
        foreach ($arr1 as $item) {
            if (Str::startsWith(trim($item), "//"))
                continue;
            $param = new RouterParamModel();
            $item = str_replace('//', '#', $item);
            $t1 = explode('\'', $item);
            if (count($t1) < 3) continue;
            $t2 = explode('|', $t1[3]);
            if (count($t2) < 2)
                ee("参数解析失败：$item");
            $t3 = explode('#', $t1[4]);
//            $param->name = str_replace('.*.', '.\*.', $t1[1]);
            $param->name = Str::of($t1[1])->replace('.*.', '.\*.')->replace('\.', '.')->toString();
            $param->required = $t2[0] == 'required';
            $param->type = $t2[1];
            $param->description = (count($t3) > 1) ? trim($t3[1]) : '-';
            $arr2[] = $param;
        }
        return $arr2;
    }

    /**
     * @param RouterControllerModel $controllerModel
     * @param RouterActionModel $action
     * @param array $actionDoc
     * @return bool|mixed
     */
    private static function getSkipPermission(RouterControllerModel $controllerModel, RouterActionModel $action, array $actionDoc): mixed
    {
        if (!$controllerModel->enableCheckPermission || $action->skipAuth || $action->skipInRouter)
            return true;

        return $actionDoc['skipPermission'] ?? false;
    }

    /**
     * @param array $routes
     * @return array
     */
    private static function buildNestedArray(array $routes): array
    {
        $result = [];

        foreach ($routes as $route) {
            $parts = explode('/', $route);
            $current = &$result;

            foreach ($parts as $index => $part) {
                if (!isset($current['children'])) {
                    $current['children'] = [];
                }

                $found = false;
                foreach ($current['children'] as &$child) {
                    if ($child['key'] == $part) {
                        $current = &$child;
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $path = implode('.', array_slice($parts, 0, $index + 1));
                    $value = $index < count($parts) - 1 ? $path . '.*' : $path;
                    $newNode = ['key' => $part, 'value' => $value];
                    $current['children'][] = $newNode;
                    $current = &$newNode;
                }
            }
        }

        // 移除 'children' 键如果它是空的
        array_walk_recursive($result, function (&$value, $key) {
            if ($key === 'children' && empty($value)) {
                unset($value);
            }
        });

        return $result;
    }
}
