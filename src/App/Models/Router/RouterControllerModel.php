<?php

namespace LaravelDev\App\Models\Router;


class RouterControllerModel
{
    public string $namespace;
    public string $className;
    public string $fullClassName;
    public ?string $name = null;
    /**
     * @var string[]
     */
    public array $moduleNames;
    public string $intro;
    public string $routerPrefix;
    public string $routerName;
    /**
     * @var RouterActionModel[]
     */
    public array $actions = [];
    public bool $enableCheckPermission = false;
}
