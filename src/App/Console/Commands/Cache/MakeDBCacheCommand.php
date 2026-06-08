<?php

namespace LaravelDev\App\Console\Commands\Cache;


use LaravelDev\App\Console\Commands\BaseCommand;
use LaravelDev\App\Services\DBServices;

class MakeDBCacheCommand extends BaseCommand
{
    protected $signature = 'cdb';
    protected $description = 'Cache DB Models';

    /**
     * @return int
     */
    public function handle(): int
    {
        DBServices::Cache();
        $this->line('db cached...');

        return self::SUCCESS;
    }
}
