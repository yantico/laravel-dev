<?php

namespace LaravelDev\App\Services;

use LaravelDev\App\Models\Database\DBTableModel;
use function Jawira\PlantUml\encodep;

class PlantUMLServices
{
    /**
     * @return array
     */
    public static function GetErMapsForOpenApi(): array
    {
        $config = config('project.erMaps') ?? [];
        $maps = [];
        foreach ($config as $name => $tables) {
            $content = self::getErMapMarkdownByTables($tables);
            $encode = encodep($content);
            $maps[] = [
                'title' => $name,
                'key' => $encode,
                'isLeaf' => true,
            ];
        }
        return $maps;
    }

    /**
     * @param string $name
     * @return string
     */
    public static function getErMap(string $name): string
    {
        $config = config('project.erMaps') ?? [];
        if (!array_key_exists($name, $config))
            return '# Not Found';

        $tables = $config[$name];

        return self::getErMapMarkdownByTables($tables);

    }

    /**
     * @param array $tables
     * @return string
     */
    private static function getErMapMarkdownByTables(array $tables): string
    {
        $entityStr = "";
        $relationStr = "";

        foreach (DBServices::GetFromCache()->tables as $table) {
            if (in_array($table->name, $tables)) {
                self::parseEntity($table, $entityStr);
                self::parseRelation($table, $relationStr, $tables);
            }
        }
        return "$entityStr\n$relationStr";
    }

    /**
     * @param DBTableModel $table
     * @param string $stringBuilder
     * @return void
     */
    private static function parseEntity(DBTableModel $table, string &$stringBuilder): void
    {
        $name = !empty($table->comment) ? $table->comment : $table->name;
        $stringBuilder .= "entity \"$name\" as $table->name {\n";

        # pk
        foreach ($table->columns as $column) {
            if ($column->isPrimaryKey || $column->name === 'id')
                $stringBuilder .= "\t* $column->name <<PK>>\n";
        }
        $stringBuilder .= "\t--\n";

        # fk
        $hasFk = false;
        foreach ($table->columns as $column) {
            if ($column->isForeignKey) {
                $prefix = $column->nullable ? '-' : '+';
                $desc = !empty($column->description) ? " : $column->description" : "";
                $stringBuilder .= "\t$prefix $column->name{$desc} <<FK>>\n";
                $hasFk = true;
            }
        }
        if ($hasFk)
            $stringBuilder .= "\t--\n";

        # fields
        foreach ($table->columns as $column) {
            if ($column->isPrimaryKey || $column->name === 'id' || $column->isForeignKey)
                continue;
            if (in_array($column->name, ['created_at', 'updated_at', 'deleted_at']))
                continue;

            $prefix = $column->nullable ? '-' : '+';
            $desc = !empty($column->description) ? " : $column->description" : "";
            $stringBuilder .= "\t$prefix $column->name{$desc}\n";
        }

        $stringBuilder .= "}\n\n";
    }

    /**
     * @param DBTableModel $table
     * @param string $relationStr
     * @param array $tables
     * @return void
     */
    private static function parseRelation(DBTableModel $table, string &$relationStr, array $tables): void
    {
        foreach ($table->belongsTo as $item) {
            if (!in_array($item['table'], $tables))
                continue;
            $relationStr .= "$table->name::{$item['foreignKey']} \"N\" --o \"1\" {$item['table']}::id\n";
        }
    }
}