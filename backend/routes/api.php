
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehiculoController;

Route::prefix('v1')->group(function () {

    // Rutas públicas
  Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1'); // 6 intentos por minuto

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me',     [AuthController::class, 'me']);
    Route::post('/logout',[AuthController::class, 'logout']);
});
   Route::get('vehicles/due-for-service', [VehiculoController::class, 'dueForService']);

    Route::apiResource('vehicles', VehiculoController::class)
        ->parameters(['vehicles' => 'vehiculo']);   // /vehicles/{vehiculo}
});
