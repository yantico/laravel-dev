<?php

namespace LaravelDev\App\Middlewares;

use Closure;
use Illuminate\Http\Request;
use LaravelDev\App\Exceptions\Err;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionMiddleware
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return Response
     * @throws Err
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 去掉 api/
        $uri = substr($request->route()->uri, 4);

        // 获取 guardName
        $guardName = str()->of($uri)->before('/')->camel()->ucfirst()->toString();
//        if (!in_array($guardName, config('project.enableCheckPermissionModules', [])))
//            return $next($request);

        // 获取用户
        $user = auth()->guard($guardName)->user();
        if (!$user)
            ee('请先登录');

        // 获取用户对应的后端api接口列表
        $apis = [];
        $user->getAllPermissions()->pluck('backend_apis')->each(function ($item) use (&$apis) {
            if ($item)
                $apis = array_merge($apis, json_decode($item));
        });
        $apis = array_unique($apis);

        // 检查是否有权限
        if (!in_array($uri, $apis))
            ee('没有权限');

        return $next($request);
    }
}
