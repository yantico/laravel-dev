<?php

namespace LaravelDev\App\Console\Commands\Dump;

use LaravelDev\App\Console\Commands\BaseCommand;
use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Services\DBServices;

class DumpTableCommand extends BaseCommand
{
    protected $name = 'dt';
    protected $description = 'dump the fields of the table';

    /**
     * @return int
     * @throws Err
     */
    public function handle(): int
    {
        list($name,) = $this->getNameAndForce();
        $tableName = str()->of($name)->snake()->singular()->plural();

        $table = DBServices::GetTable($tableName);

        $this->warn('Gen Table template');
        $this->line("protected \$table = '$table->name';");
        $this->line("protected string \$comment = '$table->comment';");
        $this->line("protected \$fillable = [{$table->getFillable()}];");

        $this->warn('gen Validate template');
        $this->line(implode("\n", $table->getValidates()));

        $this->warn('gen Insert template');
        $this->line(implode("\n", $table->getInserts()));

        return self::SUCCESS;
    }
}
