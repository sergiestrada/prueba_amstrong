<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Vehiculo;

echo "Rol::ADMINISTRADOR = " . var_export(Rol::ADMINISTRADOR, true) . "\n";
echo "Rol::CAPTURISTA    = " . var_export(Rol::CAPTURISTA,    true) . "\n\n";

$u = Usuario::with('rol')->where('email', 'admin@test.com')->first();
if (! $u) { echo "NO existe admin@test.com\n"; exit; }

echo "email:          {$u->email}\n";
echo "activo:         " . var_export((bool)$u->activo, true) . "\n";
echo "rol_id:         " . var_export($u->rol_id, true) . "\n";
echo "rol.nombre:     " . var_export($u->rol?->nombre, true) . "\n";
echo "esAdministrador: " . var_export($u->esAdministrador(), true) . "\n";
echo "puede crear:    " . var_export($u->can('create', Vehiculo::class), true) . "\n";