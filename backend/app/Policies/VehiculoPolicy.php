<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\Vehiculo;

class VehiculoPolicy
{
    public function viewAny(Usuario $user): bool
    {
        return (bool) $user->activo;
    }

    public function view(Usuario $user, Vehiculo $vehiculo): bool
    {
        return (bool) $user->activo;
    }

    public function create(Usuario $user): bool
    {
        return (bool) $user->activo && $user->esAdministrador();
    }

    public function update(Usuario $user, Vehiculo $vehiculo): bool
    {
        return (bool) $user->activo && $user->esAdministrador();
    }

    public function delete(Usuario $user, Vehiculo $vehiculo): bool
    {
        return (bool) $user->activo && $user->esAdministrador();
    }
}