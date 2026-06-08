<?php

namespace LaravelDev\App\Models\Enum;

class EnumModel
{
    public string $className;
    public string $intro;
    public string $field;
    /**
     * @var EnumConstantModel[]
     */
    public array $constants = [];

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
}
