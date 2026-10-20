<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Solo dos roles según RF-03
        Rol::updateOrCreate(['id' => 1], ['nombre' => 'administrador']);
        Rol::updateOrCreate(['id' => 2], ['nombre' => 'capturista']);
    }
}