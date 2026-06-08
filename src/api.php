<?php

use Illuminate\Support\Facades\Route;
use LaravelDev\App\Http\Controllers\DocController;

if (config('project.showDoc')) {
    Route::middleware(['api'])->prefix('/api/docs')->get('openapi', [DocController::class, 'getOpenApi']);
    Route::middleware(['api'])->prefix('/api/docs')->get('plantuml', [DocController::class, 'getErMap']);
}
