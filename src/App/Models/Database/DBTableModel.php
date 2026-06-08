<?php

namespace LaravelDev\App\Models\Database;

class DBTableModel
{
    public string $name;
    public string $modelName;
    public string $comment;
    /**
     * @var DBTableColumnModel[]
     */
    public array $columns = [];
    /**
     * @var string[]
     */
    public array $columnNames = [];
//    public ?string $guardName = null;

    public bool $skipModel = false;

    public bool $hasNodeTrait = false;
    public bool $hasApiTokens = false;
    public bool $hasRoles = false;
    public bool $hasSoftDelete = false;
    public bool $hasTags = false;


    public array $hiddenColumns = [];
    public array $jsonColumns = [];
    public array $foreignColumns = [];

    public array $hasMany = [];
    public array $belongsTo = [];

    const IGNORE_FIELDS = ['id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @return false|string
     */
    public function toJson(): false|string
    {
        return json_encode($this);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return json_decode($this->toJson(), true);
    }

    /**
     * @return void
     */
    public function dump(): void
    {
        echo($this->toJson());
    }


    /**
     * @return string
     */
    public function getFillable(): string
    {
        $fields = array_diff($this->columnNames, self::IGNORE_FIELDS);
        return "'" . implode("', '", $fields) . "'";
    }

    /**
     * @return string
     */
    public function getProperties(): string
    {
        $properties = [];

        foreach ($this->columns as $column)
            $properties[] = " * @property " . $column->propertyType . " $" . $column->name;

        return implode("\n", $properties);
    }

    /**
     * @return string
     */
    public function getImportClasses(): string
    {
        $classes = [];

        if ($this->hasRoles)
            $classes[] = "use Spatie\Permission\Traits\HasRoles;";

        if ($this->hasApiTokens)
            $classes[] = "use Laravel\Sanctum\HasApiTokens;";

        if ($this->hasNodeTrait)
            $classes[] = "use Kalnoy\Nestedset\NodeTrait;";

        if ($this->hasTags)
            $classes[] = "use Spatie\Tags\HasTags;";

        if (count($this->hasMany) || count($this->belongsTo)) {
            $classes[] = "use Illuminate\Database\Eloquent\Relations;";
            $classes[] = "use App\Models;";
        }

        if ($this->hasSoftDelete)
            $classes[] = "use Illuminate\Database\Eloquent\SoftDeletes;";

        return implode("\n", $classes);
    }

    /**
     * @return string
     */
    public function getTraits(): string
    {
        $traits = [];

        if ($this->hasRoles)
            $traits[] = "use HasRoles;";

        if ($this->hasApiTokens)
            $traits[] = "use HasApiTokens;";

        if ($this->hasNodeTrait)
            $traits[] = "use NodeTrait;";

        if ($this->hasSoftDelete)
            $traits[] = "use SoftDeletes;";

        if ($this->hasTags)
            $traits[] = "use HasTags;";

        return implode("\n\t", $traits);
    }

    /**
     * @return string
     */
    public function getHidden(): string
    {
        return count($this->hiddenColumns) ? "protected \$hidden = ['" . implode("', '", $this->hiddenColumns) . "'];" : '';
    }

    /**
     * @return string
     */
    public function getCasts(): string
    {
        if (!count($this->jsonColumns))
            return '';

        return "protected \$casts = [\n" . implode(",\n", array_map(function ($column) {
                return "        '$column' => 'array'";
            }, $this->jsonColumns)) . "\n    ];";
    }

    /**
     * @return string
     */
    public function getRelations(): string
    {
        $str = "# relations" . PHP_EOL;

        // hasMany
        foreach ($this->hasMany as $key => $value) {
            $str .= "    public function $key(): Relations\HasMany
    {
        return \$this->hasMany(Models\\{$value['related']}::class, '{$value['foreignKey']}', '{$value['localKey']}');
    }" . PHP_EOL . PHP_EOL;
        }

        // belongsTo
        foreach ($this->belongsTo as $key => $value) {
            $str .= "    public function $key(): Relations\BelongsTo
    {
        return \$this->belongsTo(Models\\{$value['related']}::class, '{$value['foreignKey']}', '{$value['ownerKey']}');
    }" . PHP_EOL . PHP_EOL;
        }

        return $str;
    }

    /**
     * @return string[]
     */
    public function getValidates(): array
    {
        $validates = [];
        foreach ($this->columns as $column) {
            if (in_array($column->name, self::IGNORE_FIELDS))
                continue;

            $required = $column->required ? 'required' : 'nullable';
            $validates[] = "'$column->name' => '$required|$column->validateType', # $column->description";
        }
        return $validates;
    }

    /**
     * @return array
     */
    public function getInserts(): array
    {
        $inserts = [];
        foreach ($this->columns as $column) {
            if (in_array($column->name, self::IGNORE_FIELDS))
                continue;

            $inserts[] = "'$column->name' => '', # $column->description";
        }
        return $inserts;
    }
}
