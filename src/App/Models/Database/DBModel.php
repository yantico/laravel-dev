<?php

namespace LaravelDev\App\Models\Database;


class DBModel
{
    /**
     * @var DBTableModel[]
     */
    public array $tables = [];
    /**
     * @var string[]
     */
    public array $tableKeys = [];

    /**
     * @return false|string
     */
    public function toJson(): false|string
    {
        return json_encode($this);
    }
}
