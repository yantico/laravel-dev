<?php

namespace LaravelDev\App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Helpers\DocBlockReader;
use LaravelDev\App\Models\Enum\EnumConstantModel;
use LaravelDev\App\Models\Enum\EnumModel;
use ReflectionClass;
use ReflectionException;

class EnumServices
{
    /**
     * @return void
     * @throws ReflectionException
     */
    public static function Cache(): void
    {
        Cache::store('file')->put('_dev_enum', self::ReflectEnumToModel());
    }

    /**
     * @return EnumModel[]
     * @throws ReflectionException
     */
    public static function GetFromCache(): array
    {
        if (App::environment('local')) {
            return self::ReflectEnumToModel();
        } else {
            return Cache::store('file')->rememberForever('_dev_enum', function () {
                logger()->channel('stderr')->debug('EnumServices::GetFromCache... cache missed');
                return self::ReflectEnumToModel();
            });
        }
    }

    /**
     * @return EnumModel[]
     * @throws ReflectionException
     */
    public static function ReflectEnumToModel(): array
    {
        $files = File::allFiles(app_path('Enums'));
        $enums = [];
        foreach ($files as $file) {
            $className = str_replace('.php', '', $file->getFilename());
            $namespace = '\\App\\Enums\\' . $className;

            $enumRef = new ReflectionClass($namespace);
            $classDoc = DocBlockReader::parse($enumRef->getDocComment());

            $enumModel = new EnumModel();
            $enumModel->className = last(explode("\\", $namespace));
            $enumModel->intro = $classDoc['intro'] ?? '';
            $enumModel->field = $classDoc['field'] ?? '';

            foreach ($enumRef->getConstants() as $constRef) {
                $constDoc = DocBlockReader::parse($enumRef->getReflectionConstant($constRef->name)->getDocComment());
                $constModel = new EnumConstantModel();
                $constModel->label = $constDoc['label'] ?? $constRef->name;
                $constModel->value = $constDoc['value'] ?? $constRef->value;
                $constModel->color = $constDoc['color'] ?? self::getRandomColor();
                $constModel->textColor = $constDoc['textColor'] ?? self::getTextColor($constModel->color);
                $enumModel->constants[] = $constModel;
            }

            $enums[$enumModel->className] = $enumModel;
        }

        return $enums;
    }

    /**
     * @param string $className
     * @return EnumModel
     * @throws ReflectionException
     * @throws Err
     */
    public static function GetEnumModelByClass(string $className): EnumModel
    {
        $className = last(explode('\\', $className));
        $enums = self::GetFromCache();
        $enum = $enums[$className] ?? null;
        if (!$enum)
            ee("枚举类不存在");
        return $enum;
    }

    /**
     * @return string
     */
    private static function getRandomColor(): string
    {
        $str = '#';
        for ($i = 0; $i < 6; $i++) {
            $str .= dechex(rand(0, 15));
        }
        return $str;
    }

    /**
     * @param $colorInput
     * @return string
     */
    private static function getTextColor($colorInput): string
    {
        $colorNamesToHex = [
            "red" => "FF0000",
            'green' => '00FF00',
            'blue' => '0000FF',
            'yellow' => 'FFFF00'
        ];

        // 如果输入的是颜色名称，将其转换为十六进制颜色代码
        if (array_key_exists($colorInput, $colorNamesToHex)) {
            $colorInput = $colorNamesToHex[$colorInput];
        }

        // 如果输入的颜色代码带有#，去掉#
        $hexColor = str_replace("#", "", $colorInput);

        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        $luminance = ($r * 0.299 + $g * 0.587 + $b * 0.114) / 255;

        if ($luminance > 0.5) {
            // 浅色背景，使用深色文字
            return '#000000'; // 黑色
        } else {
            // 深色背景，使用浅色文字
            return '#FFFFFF'; // 白色
        }
    }
}
