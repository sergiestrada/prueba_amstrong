<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrdenController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\VehiculoController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
      Route::get('/me',      [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ── Vehicles ─────────────────────────────────────────────────
    // 1º rutas fijas (no paramétricas)
    Route::get('vehicles/due-for-service', [VehiculoController::class, 'dueForService']);

    // 2º apiResource con parámetro singular en español para
    //    que el binding coincida con la firma del controller
    Route::apiResource('vehicles', VehiculoController::class)
        ->parameters(['vehicles' => 'vehiculo']);

    // ── Orders ───────────────────────────────────────────────────
    Route::patch('orders/{orden}/status', [OrdenController::class, 'updateStatus']);

    Route::apiResource('orders', OrdenController::class)
        ->parameters(['orders' => 'orden']);

    // ── Reports ──────────────────────────────────────────────────
    Route::get('v1/reports/costs', [ReportController::class, 'costs']);
});
});