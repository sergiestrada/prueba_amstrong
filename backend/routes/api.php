
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::prefix('v1')->group(function () {

    // Rutas públicas
  Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1'); // 6 intentos por minuto

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me',     [AuthController::class, 'me']);
    Route::post('/logout',[AuthController::class, 'logout']);
});

});
