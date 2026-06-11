<?php

use Illuminate\Support\Facades\Route;
use LaravelDev\App\Http\Controllers\DocController;
use LaravelDev\App\Middlewares\JsonWrapperMiddleware;

if (config('project.showDoc')) {
    Route::middleware(['api'])->prefix('/api/docs')->get('openapi', [DocController::class, 'getOpenApi'])
        ->withoutMiddleware([JsonWrapperMiddleware::class]);
    Route::middleware(['api'])->prefix('/api/docs')->get('plantuml', [DocController::class, 'getErMap'])
        ->withoutMiddleware([JsonWrapperMiddleware::class]);
}
