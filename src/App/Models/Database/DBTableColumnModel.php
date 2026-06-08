<?php

namespace LaravelDev\App\Models\Database;

class DBTableColumnModel
{
    public string $name;
    public string $typeName;
    public string $type;
    public string $propertyType;
    public string $validateType;
    public bool $nullable;
    public mixed $default;
    public bool $required;
    public ?string $description;
    public ?bool $isPrimaryKey = false;
    public ?bool $isForeignKey = false;
}
