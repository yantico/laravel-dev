<?php

namespace LaravelDev\App\Console\Commands\GenFiles;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Models\Database\DBTableModel;
use LaravelDev\App\Services\DBServices;

class GenDBErMapCommand extends Command
{
    protected $signature = 'ger';
    protected $description = 'Command description';

    /**
     * @return void
     * @throws Err
     */
    public function handle(): void
    {
        $erMaps = config('project.erMaps') ?? null;

        // 生成单个模型文件
        foreach ($erMaps as $key => $tables) {
            $entityStr = '';
            $relationStr = '';
            foreach ($tables as $tableName) {
                $table = DBServices::getTable($tableName);
                if (!$table->comment)
                    continue;

                $this->parseEntity($table, $entityStr);
                $this->parseRelation($table, $relationStr);
            }
            $namespaceStart = "namespace {$key} {";
            $this->save($key, "$namespaceStart\n$entityStr}\n\n$relationStr\n");
        }

        // 生成所有
        $entityStr = '';
        $relationStr = '';
        foreach (DBServices::GetFromCache()->tables as $table) {
            if (!$table->comment)
                continue;

            $this->parseEntity($table, $entityStr);
            $this->parseRelation($table, $relationStr);
        }
        $this->save('database', "$entityStr\n\n$relationStr");
    }

    /**
     * @param string $key
     * @param string $stringBuilder
     * @return void
     */
    private function save(string $key, string $stringBuilder): void
    {
        $path = database_path('maps');
        if (!File::exists($path))
            File::makeDirectory($path);

        $filePath = $path . '/' . $key . '.puml';
        dump('save file: ' . $filePath);
        File::put($filePath, '@startuml' . PHP_EOL . $stringBuilder . PHP_EOL . '@enduml');
    }

    /**
     * @param DBTableModel $table
     * @param string $stringBuilder
     * @return void
     */
    private function parseEntity(DBTableModel $table, string &$stringBuilder): void
    {
        $stringBuilder .= "\tentity \"$table->comment\\n$table->name\" as $table->name {" . PHP_EOL;

        # pk
        foreach ($table->columns as $column) {
            if ($column->isPrimaryKey)
                $stringBuilder .= "\t\t* $column->name <<PK>>\n";
        }
        $stringBuilder .= "\t\t--\n";

        # fk
        $hasFk = false;
        foreach ($table->columns as $column) {
            if ($column->isForeignKey) {
                $prefix = $column->nullable ? '-' : '+';
                $stringBuilder .= "\t\t$prefix $column->name : $column->description <<FK>>\n";
                $hasFk = true;
            }
        }
        if ($hasFk)
            $stringBuilder .= "\t\t--\n";

        # fields
        foreach ($table->columns as $column) {
            if ($column->isPrimaryKey || $column->isForeignKey)
                continue;

            if (!$column->description)
                continue;

            $prefix = $column->nullable ? '-' : '+';
            $stringBuilder .= "\t\t$prefix $column->description\n";
        }

        $stringBuilder .= "\t}\n\n";
    }

    /**
     * @param DBTableModel $table
     * @param string $relationStr
     * @return void
     */
    private function parseRelation(DBTableModel $table, string &$relationStr): void
    {
        foreach ($table->belongsTo as $item) {
            $relationStr .= "$table->name::{$item['foreignKey']} \"N\" --o \"1\" {$item['table']}::id\n";
        }
    }
}
