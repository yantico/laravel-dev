<?php

namespace LaravelDev\App\Console\Commands\Dump;

use LaravelDev\App\Console\Commands\BaseCommand;
use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Services\DBServices;

class DumpDBTableModelCommand extends BaseCommand
{
    protected $name = 'ddb';
    protected $description = 'Dump DB Model';

    /**
     * @return int
     * @throws Err
     */
    public function handle(): int
    {
        list($name,) = $this->getNameAndForce();
        $tableName = str()->of($name)->snake()->singular()->plural();

        $table = DBServices::GetTable($tableName);
        $table->dump();

        return self::SUCCESS;
    }
}
