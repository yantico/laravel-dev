<?php

namespace LaravelDev\App\Console\Commands\Cache;

use LaravelDev\App\Console\Commands\BaseCommand;
use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Services\RouterServices;
use ReflectionException;

class MakeRouterCacheCommand extends BaseCommand
{
    protected $signature = 'cr';
    protected $description = 'Cache Router Models';

    /**
     * @return int
     * @throws ReflectionException
     * @throws Err
     */
    public function handle(): int
    {
        RouterServices::Cache();
        $this->line('router cached...');

        return self::SUCCESS;
    }
}
