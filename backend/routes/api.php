
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehiculoController;
use App\Http\Controllers\Api\OrdenController;
use App\Http\Controllers\Api\ReportController;

Route::prefix('v1')->group(function () {

    // Rutas públicas
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1'); // 6 intentos por minuto

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',     [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
    Route::get('vehicles/due-for-service', [VehiculoController::class, 'dueForService']);

    Route::apiResource('vehicles', VehiculoController::class)
        ->parameters(['vehicles' => 'vehiculo']);   // /vehicles/{vehiculo}

      Route::get('vehicles/due-for-service', [VehiculoController::class, 'dueForService']);
    Route::apiResource('vehicles', VehiculoController::class)
        ->parameters(['vehicles' => 'vehiculo']);

    // Órdenes
    Route::patch('orders/{orden}/status', [OrdenController::class, 'updateStatus']);
    Route::apiResource('orders', OrdenController::class)
        ->parameters(['orders' => 'orden'])
        ->only(['index', 'store', 'show', 'update']);

    // Reportes
    Route::get('reports/costs', [ReportController::class, 'costs']);
});
