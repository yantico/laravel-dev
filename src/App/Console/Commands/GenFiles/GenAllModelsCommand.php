<?php

namespace LaravelDev\App\Console\Commands\GenFiles;

use Illuminate\Support\Facades\Artisan;
use LaravelDev\App\Console\Commands\BaseCommand;
use LaravelDev\App\Services\DBServices;

class GenAllModelsCommand extends BaseCommand
{
    protected $name = 'gam';
    protected $description = '生成所有的模型文件';

    /**
     * @return int
     */
    public function handle(): int
    {
        foreach (DBServices::GetFromCache()->tables as $table)
            if (!$table->skipModel)
                Artisan::call("gd -f $table->name");

        return self::SUCCESS;
    }
}
