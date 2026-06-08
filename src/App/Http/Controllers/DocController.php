<?php

namespace LaravelDev\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Services\OpenApiServices;
use LaravelDev\App\Services\PlantUMLServices;
use ReflectionException;

class DocController extends Controller
{
    /**
     * @return JsonResponse
     * @throws ReflectionException
     * @throws Err
     */
    public function getOpenApi(): JsonResponse
    {
//        sleep(3);
        return response()->json(OpenApiServices::ToArray());
    }

    /**
     * @return string
     */
    public function getErMap(): string
    {
        $params = request()->validate([
            'name' => 'required|string', # name of the er map
        ]);
        return PlantUMLServices::getErMap($params['name']);
    }
}
