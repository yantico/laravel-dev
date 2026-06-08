<?php

namespace LaravelDev\App\Traits;

use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Services\EnumServices;
use ReflectionException;

trait EnumTrait
{
    /**
     * @return array
     */
    public static function Values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @param string $name
     * @return string
     */
    public static function Comment(string $name): string
    {
        return $name . ':' . str_replace("App\\Enums\\", "", self::class);  //. implode(',', self::columns());
    }

    /**
     * @param string $label
     * @param bool $throw
     * @return string|int|null
     * @throws Err
     * @throws ReflectionException
     */
    public static function GetValueByLabel(string $label, bool $throw = true): string|int|null
    {
        if (!$label && $throw)
            ee("枚举值不能为空");
        $enum = EnumServices::GetEnumModelByClass(self::class);
        foreach ($enum->constants as $const)
            if ($const->label == $label)
                return $const->value;
        if ($throw)
            ee("枚举值不存在");
        return null;
    }

    /**
     * @param mixed|null $value
     * @param bool $throw
     * @return string|null
     * @throws Err
     * @throws ReflectionException
     */
    public static function GetLabelByValue(string|int $value, bool $throw = true): ?string
    {
        if (!$value && $throw)
            ee("枚举值不能为空");
        $enum = EnumServices::GetEnumModelByClass(self::class);
        foreach ($enum->constants as $const)
            if ($const->value == $value)
                return $const->label;
        if ($throw)
            ee("枚举值不存在");
        return null;
    }

    /**
     * @param mixed|null $value
     * @param bool $throw
     * @return bool
     * @throws Err
     */
    public static function IsValueInEnum(mixed $value = null, bool $throw = true): bool
    {
        if (!in_array($value, self::Values())) {
            if ($throw)
                ee("枚举值不存在");
            return false;
        }
        return true;
    }

    /**
     * @return array|string[]
     * @throws Err
     * @throws ReflectionException
     */
    public static function GetLabels(): array
    {
        return array_map(function ($item) {
            return $item->label;
        }, EnumServices::GetEnumModelByClass(self::class)->constants);
    }

    /**
     * @return int
     */
    public static function GetMaxLength(): int
    {
        $arr = array_map(function ($item) {
            return strlen($item->value);
        }, self::cases());
        return max($arr);
    }
}
