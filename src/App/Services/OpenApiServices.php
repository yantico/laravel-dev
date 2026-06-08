<?php

namespace LaravelDev\App\Services;

use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Models\Database\DBModel;
use LaravelDev\App\Models\Database\DBTableModel;
use LaravelDev\App\Models\Enum\EnumModel;
use LaravelDev\App\Models\Router\RouterControllerModel;
use ReflectionException;

class OpenApiServices
{
    /**
     * @return array
     * @throws Err
     * @throws ReflectionException
     */
    public static function ToArray(): array
    {
        $routers = RouterServices::GetFromCache();
        $db = DBServices::GetFromCache();
        $enums = EnumServices::GetFromCache();

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => config('app.name'),
                'version' => '0.0.x',
            ],
            'servers' => [
                [
                    "url" => config('app.url') . "/api/",
                    "description" => "Server Address"
                ]
            ],
            'tags' => self::getTags($routers),
            'paths' => self::getPaths($routers),
            'x-database' => self::getDatabases($db),
            'x-enum' => self::getEnums($enums),
            'x-plantuml' => PlantUMLServices::GetErMapsForOpenApi()
//            'x-plant-uml' => [
//                'server' => config('project.plantUmlServer', 'https://www.plantuml.com/plantuml/svg/'),
//                'items' => PlantUMLServices::GetErMapsForOpenApi(),
//            ],
        ];
    }

    /**
     * @param array $routers
     * @return array|array[]
     */
    private static function getTags(array $routers): array
    {
        return array_map(function ($router) {
            return [
                'name' => self::getTagByRouter($router),
                'description' => $router->intro,
            ];
        }, $routers);
    }

    /**
     * @param RouterControllerModel[] $routers
     * @return array|array[]
     */
    private static function getPaths(array $routers): array
    {
        $paths = [];
        foreach ($routers as $router) {
//            $pathPrefix = implode('/', $router->moduleNames) . '/' . $router->name . '/';
            $tags = self::getTagByRouter($router);
            foreach ($router->actions as $action) {
                $path = $router->routerPrefix . '/' . $action->uri;
                $method = strtolower($action->methods[0]);
                $data = [
                    "tags" => [$tags],
                    "summary" => $action->uri,
                    "description" => $action->intro,
                ];

                if (count($action->requestBody)) {
                    $properties = [];
                    $required = [];
                    foreach ($action->requestBody as $param) {
                        $properties[$param->name] = [
                            "type" => $param->type,
                            "description" => $param->description,
                            "required" => $param->required,
                        ];
                        if ($param->required) {
                            $required[] = $param->name;
                        }
                    }
                    $data['requestBody'] = [
                        "content" => [
                            'application/x-www-form-urlencoded' => [
                                "schema" => [
                                    "type" => "object",
                                    "properties" => $properties ?? [],
                                    "required" => $required,
                                ]
                            ]
                        ],
                    ];
                }

                if ($action->responseJson)
                    $data['x-response-json'] = $action->responseJson;

                if ($action->responseBody)
                    $data['x-response-body'] = $action->responseBody;

                if ($action->isDownload)
                    $data['x-is-download'] = true;

                $paths[$path] = [
                    $method => $data
                ];
            }
        }
        return $paths;
    }

    /**
     * @param mixed $router
     * @return string
     */
    private static function getTagByRouter(mixed $router): string
    {
        return implode('/', $router->moduleNames) . '/' . $router->className;
    }

    /**
     * @param DBModel $db
     * @return array
     */
    private static function getDatabases(DBModel $db): array
    {
        $schemas = [];
        foreach ($db->tables as $table) {
            if ($table->skipModel)
                continue;
            $schemas[$table->name] = [
                "title" => $table->comment,
                "properties" => self::getTableProperties($table)
            ];
        }
        return $schemas;
    }

    /**
     * @param EnumModel[] $enums
     * @return array
     */
    private static function getEnums(array $enums): array
    {
        $schemas = [];
        foreach ($enums as $enum) {
            $schemas[$enum->className] = [
                "title" => $enum->intro,
                "field" => $enum->field,
                "properties" => self::getEnumProperties($enum)
            ];
        }
        return $schemas;
    }

    /**
     * @param DBTableModel $table
     * @return array
     */
    private static function getTableProperties(DBTableModel $table): array
    {
        $properties = [];
        foreach ($table->columns as $name => $column) {
            $properties[$name] = [
                'name' => $column->name,
                'type' => $column->type,
                'description' => $column->description,
                "required" => $column->required,
                "default" => $column->default,
                'x-type-name' => $column->typeName,
                'x-property-type' => $column->propertyType,
                'x-validate-type' => $column->validateType,
            ];
        }
        return $properties;
    }

    /**
     * @param EnumModel $enum
     * @return array
     */
    private static function getEnumProperties(EnumModel $enum): array
    {
        $properties = [];
        foreach ($enum->constants as $key => $value) {
            $properties[$key] = [
                'label' => $value->label,
                'value' => $value->value,
                "color" => $value->color,
                "textColor" => $value->textColor,
            ];
        }
        return $properties;
    }
}
