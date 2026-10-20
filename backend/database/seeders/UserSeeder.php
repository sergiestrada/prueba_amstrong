<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRol = Rol::where('nombre', 'administrador')->firstOrFail();
        $captRol  = Rol::where('nombre', 'capturista')->firstOrFail();

        Usuario::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'nombre'   => 'Admin',
                'apellido' => 'Sistema',
                'password' => Hash::make('password'),
                'rol_id'   => $adminRol->id,
                'activo'   => true,
            ]
        );

        Usuario::updateOrCreate(
            ['email' => 'capturista@test.com'],
            [
                'nombre'   => 'Capturista',
                'apellido' => 'Sistema',
                'password' => Hash::make('password'),
                'rol_id'   => $captRol->id,
                'activo'   => true,
            ]
        );
    }
}