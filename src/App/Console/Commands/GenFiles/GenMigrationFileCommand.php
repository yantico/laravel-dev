<?php

namespace LaravelDev\App\Console\Commands\GenFiles;

use Illuminate\Support\Facades\File;
use LaravelDev\App\Console\Commands\BaseCommand;
use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Services\DBServices;

class GenMigrationFileCommand extends BaseCommand
{
    protected $name = 'gm';
    protected $description = "根据输入的数据库表名，生成migration迁移文件。
    表名：会转成蛇形，单数，复数。
    例如：php artisan gm users
    例如：php artisan gm User";

    /**
     * @return int
     * @throws Err
     */
    public function handle(): int
    {
        list($name,) = $this->getNameAndForce();

        $snakeName = str()->of($name)->snake()->__toString();
        $existingTable = DBServices::GetTableIfExists($snakeName);
        $tableName = $existingTable
            ? $existingTable->name
            : str()->of($name)->snake()->singular()->plural()->__toString();
        $table = DBServices::GetTable($tableName);

        $fileName = now()->format('Y_m_d_His') . "_create_{$tableName}_table.php";
        $filePath = database_path('migrations') . DIRECTORY_SEPARATOR . $fileName;

        $stubPath = implode(DIRECTORY_SEPARATOR, [__DIR__, 'stubs', 'migration.stub']);
        $content = File::get($stubPath);
        $content = str_replace(
            ['{{ tableName }}', '{{ columns }}'],
            [$tableName, $table->getMigrationColumns()],
            $content,
        );

        File::put($filePath, $content);
        $this->info($filePath . "\t文件生成成功");

        return self::SUCCESS;
    }
}
