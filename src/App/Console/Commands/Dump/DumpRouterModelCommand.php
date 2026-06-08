<?php

namespace LaravelDev\App\Console\Commands\Dump;

use LaravelDev\App\Console\Commands\BaseCommand;
use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Services\RouterServices;
use ReflectionException;

class DumpRouterModelCommand extends BaseCommand
{
    protected $name = 'dr';
    protected $description = 'Dump Router Model';

    /**
     * @return int
     * @throws Err
     * @throws ReflectionException
     */
    public function handle(): int
    {
        list($name,) = $this->getNameAndForce();
        $name = 'App/Modules/' . $name;
        $modules = explode('/', $name);
        $fullClassName = implode('\\', $modules) . 'Controller';
        $router = RouterServices::GetRouterModelByFullClassName($fullClassName);
        dump($router);
        return self::SUCCESS;
    }
}
