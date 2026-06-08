<?php

namespace LaravelDev\App\Console\Commands\Cache;

use LaravelDev\App\Console\Commands\BaseCommand;
use LaravelDev\App\Services\EnumServices;
use ReflectionException;

class MakeEnumCacheCommand extends BaseCommand
{
    protected $signature = 'ce';
    protected $description = 'Cache Enum Models';

    /**
     * @return int
     * @throws ReflectionException
     */
    public function handle(): int
    {
        EnumServices::Cache();
        $this->line('enum cached...');

        return self::SUCCESS;
    }
}
