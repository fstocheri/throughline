<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductionLineController;
use App\Http\Controllers\Api\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::apiResource('production-lines', ProductionLineController::class)->except(['show']);

    Route::apiResource('work-orders', WorkOrderController::class)->except(['index', 'store'])
        ->parameters(['work-orders' => 'work_order']);
    Route::get('/work-orders', [WorkOrderController::class, 'index']);
    Route::post('/work-orders', [WorkOrderController::class, 'store']);
    Route::patch('/work-orders/{work_order}/move', [WorkOrderController::class, 'move']);
    Route::get('/work-orders/{work_order}/events', [WorkOrderController::class, 'events']);
});
