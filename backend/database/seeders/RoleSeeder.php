<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Rol::updateOrCreate(['id' => 1], ['nombre' => Rol::ADMINISTRADOR]);
        Rol::updateOrCreate(['id' => 2], ['nombre' => Rol::CAPTURISTA]);
    }
}