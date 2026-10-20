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

    /** RF-03 + RN-08: solo el administrador administra vehículos. */
    public function create(Usuario $user): bool
    {
        return (bool) $user->activo && $this->hasRole($user, 'administrador');
    }

    public function update(Usuario $user, Vehiculo $vehiculo): bool
    {
        return (bool) $user->activo && $this->hasRole($user, 'administrador');
    }

    /** RN-08: solo administrador da de baja. */
    public function delete(Usuario $user, Vehiculo $vehiculo): bool
    {
        return (bool) $user->activo && $this->hasRole($user, 'administrador');
    }

    private function hasRole(Usuario $user, string $role): bool
    {
        return $user->rol?->nombre === $role;
    }
}