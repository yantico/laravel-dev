<?php

namespace LaravelDev\App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Models\Database\DBModel;
use LaravelDev\App\Models\Database\DBTableColumnModel;
use LaravelDev\App\Models\Database\DBTableModel;

class DBServices
{
    const PROPERTY_TYPES = [
        'tinyint' => 'integer',
        'smallint' => 'integer',
        'int' => 'integer',
        'bigint' => 'integer',
        'varchar' => 'string',
        'text' => 'string',
        'mediumtext' => 'string',
        'longtext' => 'string',
        'enum' => 'string',
        'date' => 'string',
        'datetime' => 'string',
        'decimal' => 'numeric',
        'double' => 'numeric',
        'json' => 'array',
        'timestamp' => 'mixed',
    ];

    const VALIDATE_TYPES = [
        'tinyint' => 'integer',
        'smallint' => 'integer',
        'int' => 'integer',
        'bigint' => 'integer',
        'varchar' => 'string',
        'text' => 'string',
        'mediumtext' => 'string',
        'longtext' => 'string',
        'enum' => 'string',
        'date' => 'date',
        'datetime' => 'date',
        'decimal' => 'numeric',
        'double' => 'numeric',
        'json' => 'array',
        'timestamp' => 'mixed',
    ];

    /**
     * @return void
     */
    public static function Cache(): void
    {
        Cache::store('file')->put('_dev_db', self::ReflectDBToModel());
    }

    /**
     * @return DBModel
     */
    public static function GetFromCache(): DBModel
    {
        return Cache::store('file')->rememberForever('_dev_db', function () {
            logger()->channel('stderr')->debug('DBServices::GetFromCache... cache missed');
            return self::ReflectDBToModel();
        });
    }

    /**
     * @param string $tableName
     * @return DBTableModel
     * @throws Err
     */
    public static function GetTable(string $tableName): DBTableModel
    {
        $dbModel = self::GetFromCache();
        $index = array_search($tableName, $dbModel->tableKeys);
        if ($index === false)
            ee("表不存在：$tableName");
        return $dbModel->tables[$index];
    }

    /**
     * @return DBModel
     */
    private static function ReflectDBToModel(): DBModel
    {
        $tables = Schema::getTables();
        $tableNames = collect($tables)->pluck('name')->toArray();
        $dbModel = new DBModel();

        $hasApiTokens = config('project.hasApiTokens') ?? [];
        $hasRoles = config('project.hasRoles') ?? [];
        $hasNodeTrait = config('project.hasNodeTrait') ?? [];
        $hasTags = config('project.hasTags') ?? [];
        $dbSkipGenModel = config('project.dbSkipGenModel') ?? [];

        // 第一次处理
        foreach ($tables as $table) {
            $dbTableModel = new DBTableModel();
            $dbTableModel->name = $table['name'];
            $dbTableModel->modelName = Str::of($table['name'])->camel()->ucfirst();
            $dbTableModel->comment = $table['comment'] ?? '';
            $dbTableModel->hasApiTokens = in_array($dbTableModel->name, $hasApiTokens);
            $dbTableModel->hasRoles = in_array($dbTableModel->name, $hasRoles);
            $dbTableModel->hasNodeTrait = in_array($dbTableModel->name, $hasNodeTrait);
            $dbTableModel->hasTags = in_array($dbTableModel->name, $hasTags);
            $dbTableModel->skipModel = in_array($dbTableModel->name, $dbSkipGenModel);

            foreach (Schema::getColumns($table['name']) as $column) {
                $dbTableColumnModel = new DBTableColumnModel();
                $dbTableColumnModel->name = $column['name'];
                $dbTableColumnModel->typeName = $column['type_name'];
                $dbTableColumnModel->type = $column['type'];
                $dbTableColumnModel->propertyType = self::PROPERTY_TYPES[$column['type_name']] ?? 'unknown:' . $column['type_name'];
                $dbTableColumnModel->validateType = self::VALIDATE_TYPES[$column['type_name']] ?? 'unknown:' . $column['type_name'];
                $dbTableColumnModel->nullable = $column['nullable'];
                $dbTableColumnModel->default = $column['default'];
                $dbTableColumnModel->description = $column['comment'];
                $dbTableColumnModel->required = !$column['nullable'] && $column['default'] === null;
                $dbTableColumnModel->isPrimaryKey = in_array($column['type_name'], ['tinyint', 'smallint', 'mediumint', 'int', 'bigint']) && $column['auto_increment'] == true;
                $dbTableModel->columns[] = $dbTableColumnModel;
                $dbTableModel->columnNames[] = $column['name'];
                if (str_contains($column['comment'], '[hidden]'))
                    $dbTableModel->hiddenColumns[] = $column['name'];
                if ($column['type'] === 'json')
                    $dbTableModel->jsonColumns[] = $column['name'];
                if ($column['name'] === 'deleted_at')
                    $dbTableModel->hasSoftDelete = true;

                // foreign key
                list($foreignTable, $foreignKey) = self::parseColumnForeignInfo($column);
                if ($foreignTable && in_array($foreignTable, $tableNames)) {
                    $dbTableColumnModel->isForeignKey = true;
                    $dbTableModel->foreignColumns[$column['name']] = [$foreignTable, $foreignKey];
                }
            }

            $dbModel->tables[] = $dbTableModel;
            $dbModel->tableKeys[] = $table['name'];
        }

        // 第二次处理
        foreach ($dbModel->tables as $tableModel) {
            $tableModel->hasMany = self::parseHasMany($dbModel, $tableModel);
            $tableModel->belongsTo = self::parseBelongsTo($tableModel);
        }

        return $dbModel;
    }

    /**
     * @param array $column
     * @return array
     */
    private static function parseColumnForeignInfo(array $column): array
    {
        $comment = $column['comment'];
        $name = $column['name'];

        // foreign key 备注中有：ref[表名,字段名]
        if (str()->of($comment)->contains("[ref:")) {
            $t1 = str()->of($comment)->between("[ref:", "]")->explode(',');
            return [str()->of($t1[0])->snake()->toString(), $t1[1] ?? 'id'];
        }

        // 如果是以_id结尾
        if (str()->of($column['name'])->endsWith('_id'))
            return [str()->of($name)->before('_id')->snake()->toString(), 'id'];

        return [null, null];
    }


    /**
     * @param DBModel $dbModel
     * @param DBTableModel $tableModel
     * @return array
     */
    private static function parseHasMany(DBModel $dbModel, DBTableModel $tableModel): array
    {
        $hasMany = [];
        foreach ($dbModel->tables as $table) {
            // 排除自己
            if ($table->name == $tableModel->name)
                continue;

            // 是否有跟自己相关的外键
            $filteredArray = array_filter($table->foreignColumns, function ($value) use ($tableModel) {
                return $value[0] === $tableModel->name;
            });
            $keys = array_keys($filteredArray);
            if (empty($keys))
                continue;

            if (count($keys) === 1) {
                $foreign = $table->foreignColumns[$keys[0]];
                $hasMany[$table->name] = [
                    'table' => $table->name,
                    'related' => str()->of($table->name)->studly()->toString(),
                    'foreignKey' => $keys[0],
                    'localKey' => $foreign[1] ?? 'id'
                ];
            } elseif (count($keys) > 1) {
                foreach ($keys as $key) {
                    $foreign = $table->foreignColumns[$key];
                    $hasMany[str()->of(str_replace('_id', '', $key))->singular()->toString() . '_' . $table->name] = [
                        'table' => $table->name,
                        'related' => str()->of($table->name)->studly()->toString(),
                        'foreignKey' => $key,
                        'localKey' => $foreign[1] ?? 'id'
                    ];
                }
            }
        }
        return $hasMany;
    }

    /**
     * @param DBTableModel $tableModel
     * @return array
     */
    private static function parseBelongsTo(DBTableModel $tableModel): array
    {
        $belongsTo = [];
        foreach ($tableModel->foreignColumns as $foreignKey => $foreignTableName) {
            list($ref, $fk) = $foreignTableName;
            $belongsTo[str()->of(str_replace('_id', '', $foreignKey))->singular()->toString()] = [
                'table' => $ref,
                'related' => str()->of($ref)->studly()->toString(),
                'foreignKey' => $foreignKey,
                'ownerKey' => $fk ?? 'id'
            ];
        }
        return $belongsTo;
    }
}
