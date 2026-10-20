<?php

namespace App\Policies;

use App\Models\Orden;
use App\Models\Usuario;

class OrdenPolicy
{
    public function viewAny(Usuario $user): bool
    {
        return (bool) $user->activo;
    }

    public function view(Usuario $user, Orden $orden): bool
    {
        return (bool) $user->activo;
    }

    public function create(Usuario $user): bool
    {
        return (bool) $user->activo
            && $this->hasAnyRole($user, ['admin', 'supervisor', 'tecnico']);
    }

    public function update(Usuario $user, Orden $orden): bool
    {
        if (! $user->activo) return false;
        // Una orden cerrada (completada/cancelada) ya no se edita
        if ($orden->estaCerrada()) return false;

        return $this->hasAnyRole($user, ['admin', 'supervisor', 'tecnico']);
    }

    public function changeStatus(Usuario $user, Orden $orden): bool
    {
        return (bool) $user->activo
            && $this->hasAnyRole($user, ['admin', 'supervisor', 'tecnico']);
    }

    public function delete(Usuario $user, Orden $orden): bool
    {
        return (bool) $user->activo
            && $this->hasAnyRole($user, ['admin']);
    }

    private function hasAnyRole(Usuario $user, array $roles): bool
    {
        return in_array($user->rol?->nombre, $roles, true);
    }
}