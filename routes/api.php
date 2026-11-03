<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PartController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\SupplyController;
use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\VehicleController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/token', TokenController::class)->middleware('throttle:10,1');
Route::get('/openapi', fn () => response()->json(json_decode(file_get_contents(base_path('docs/openapi.json')), true, flags: JSON_THROW_ON_ERROR)));

Route::pattern('customer', '[0-9]+');
Route::pattern('vehicle', '[0-9]+');
Route::pattern('service', '[0-9]+');
Route::pattern('material', '[0-9]+');
Route::pattern('order', '[0-9]+');

Route::middleware('jwt.admin')->group(function () {
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('vehicles', VehicleController::class);
    Route::apiResource('services', ServiceController::class);
    Route::get('service-orders', [OrderController::class, 'index']);
    Route::post('service-orders', [OrderController::class, 'store']);
    Route::get('service-orders/{order}', [OrderController::class, 'show']);
    Route::post('service-orders/{order}/diagnosis', [OrderController::class, 'diagnose']);
    Route::put('service-orders/{order}/items', [OrderController::class, 'replaceItems']);
    foreach (['parts' => PartController::class, 'supplies' => SupplyController::class] as $resource => $controller) {
        Route::apiResource($resource, $controller)->parameters([$resource => 'material']);
        Route::get($resource.'/{material}/stock-movements', [$controller, 'movements'])->name($resource.'.stock.index');
        Route::post($resource.'/{material}/stock-movements', [$controller, 'move'])->name($resource.'.stock.store');
    }
});
