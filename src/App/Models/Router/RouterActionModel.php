<?php

namespace LaravelDev\App\Models\Router;


class RouterActionModel
{
    /**
     * 路由名称
     *  自动取function方法名称
     *  比如：userList
     * @var string
     */
    public string $functionName;
    /**
     * 请求的路径
     *  自动取function方法名称，再转snake
     *  比如：user_list
     * @var string
     */
    public string $uri;
    /**
     * 完整的uri
     *  比如：/api/admin/user_list
     * @var string
     */
    public string $fullUri;
    /**
     * 完整的类名
     *  比如：admin.user_list
     * @var string
     */
    public string $fullName;
    /**
     * 请求的方法
     *  默认POST
     *  可在方法的注解中定义 @methods GET,POST
     *  在生成的开发文档中，支持不是特别好
     * @var string[]，
     */
    public array $methods;
    public string $intro;
    /**
     * 路由参数，暂时没用到
     * @var RouterParamModel[]
     */
    public array $parameters;
    /**
     * 请求参数
     *  会根据方法中，定义￥params的参数，自动生成
     *  比如：
     *      $params = request()->validate([
     *          'username' => 'required|string', # 用户名
     *          'password' => 'required|string', # 密码
     *          'roles_id' => 'nullable|array', # 角色
     *      ]);
     * @var RouterParamModel[]
     */
    public array $requestBody;
    /**
     * 响应参数
     *  通过注解生成
     *  在api文档中会显示成可折叠的表结构
     *  比如 @responseBody [{"name":"user","type":"object","required":true,"description":"用户","children":[{"name":"id","type":"number","required":true,"description":"用户ID"},{"name":"username","type":"string","required":true,"description":"用户名"}]},{"name":"token","type":"object","required":true,"description":"令牌","children":[{"name":"access_token","type":"string","required":true,"description":"访问令牌"}]},{"name":"permissions","type":"object","required":true,"description":"权限","children":[{"name":"id","type":"number","required":true,"description":"权限ID"},{"name":"name","type":"string","required":true,"description":"权限名称"},{"name":"guard_name","type":"string","required":true,"description":"守卫名称"},{"name":"created_at","type":"string","required":true,"description":"创建时间"},{"name":"updated_at","type":"string","required":true,"description":"更新时间"},{"name":"icon","type":"string","required":false,"description":"图标"},{"name":"type","type":"string","required":true,"description":"类型"},{"name":"path","type":"string","required":false,"description":"路径"},{"name":"children","type":"array","required":true,"description":"子权限","children":[]}]}]
     * @var string|null
     */
    public ?string $responseBody;
    /**
     * 是否废弃
     * @var bool
     */
    public bool $deprecated;
    /**
     * 响应的json字符串
     *  通过注解生成
     *  在api文档中，会显示成代码块
     *  比如 @responseJson {"success":true,"data":{"id":1,"username":"zwb","last_login_ip":null,"last_login_at":null,"created_at":"2024-04-08T02:23:51.000000Z","updated_at":"2024-04-08T02:23:51.000000Z"}}
     * @var string|null
     */
    public ?string $responseJson;
    /**
     * 中间件
     *  所有接口，默认会加上[api, auth:$moduleName, JsonWrapperMiddleware::class]
     *  api: 系统自带的
     *  auth:$moduleName：用于sanctum鉴权的
     *  JsonWrapperMiddleware::class：用于返回统一的json格式的数据
     *  如果类似login接口，不需要auth中间件，则可以在方法中加上注解 @skipAuth true
     * @var string[]
     */
    public ?array $withMiddlewares;
    public ?array $withoutMiddlewares;

    /**
     * 是否跳过生成路由
     *  默认false
     *  有些接口，比如：notify，不需要自动生成路由，可以在方法中加上注解 @skipInRouter true，然后手动在路由文件中定义
     * @var bool
     */
    public bool $skipInRouter;
//    /**
//     * 是否跳过json包裹
//     *  默认false
//     *  所有接口，返回的数据，都会被json包裹，比如 {"success":true,"data":{},"message":""}
//     *  有些接口，比如：微信支付的notify，不需要json包裹，可以在方法中加上注解 @skipWrap true
//     * @var bool
//     */
//    public bool $skipWrap;
//    /**
//     * 是否跳过鉴权
//     * @var bool
//     */
//    public bool $skipAuth;
//    /**
//     * 是否跳过权限
//     *  默认false
//     *  有些接口，比如：select，不需要权限验证，可以在方法中加上注解 @skipPermission true
//     * @var bool
//     */
//    public bool $skipPermission;
    /**
     * 返回的类型
     *  主要是用来判断是否下载的StreamResponse
     * @var string
     */
    public string $return;
    public bool $isDownload;
}
