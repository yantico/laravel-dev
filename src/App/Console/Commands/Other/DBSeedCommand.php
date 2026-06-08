<?php

namespace LaravelDev\App\Console\Commands\Other;

use Illuminate\Support\Facades\Artisan;
use LaravelDev\App\Console\Commands\BaseCommand;

class DBSeedCommand extends BaseCommand
{
    protected $name = 'db:backup';
    protected $description = 'Backup DB to seed files';

    /**
     * @return int
     */
    public function handle(): int
    {
        $tableList = implode(',', config('project.dbBackupList') ?? []);
        $this->info('Start backup DB to seed files: ' . $tableList);
        Artisan::call("iseed $tableList --force --chunksize=100");

        return self::SUCCESS;
    }
}
