<?php

namespace App\Providers;

use App\Models\Orden;
use App\Models\Vehiculo;
use App\Policies\OrdenPolicy;
use App\Policies\VehiculoPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

  public function boot(): void
{
    Gate::policy(Vehiculo::class, VehiculoPolicy::class);
    Gate::policy(Orden::class,    OrdenPolicy::class);

    // Permite que las peticiones autenticadas por token se resuelvan como Usuario.
    \Laravel\Sanctum\Sanctum::usePersonalAccessTokenModel(
        \Laravel\Sanctum\PersonalAccessToken::class
    );
}
}