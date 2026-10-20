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

    /** RN-08: administrador y capturista crean órdenes. */
    public function create(Usuario $user): bool
    {
        return (bool) $user->activo
            && in_array($user->rol?->nombre, ['administrador', 'capturista'], true);
    }

    /** RN-05: no se edita orden cerrada/cancelada. RN-08: ambos roles. */
    public function update(Usuario $user, Orden $orden): bool
    {
        if (! $user->activo) return false;
        if ($orden->estaCerrada()) return false;

        return in_array($user->rol?->nombre, ['administrador', 'capturista'], true);
    }

    public function changeStatus(Usuario $user, Orden $orden): bool
    {
        return (bool) $user->activo
            && in_array($user->rol?->nombre, ['administrador', 'capturista'], true);
    }

    /** RN-08: solo el administrador puede cancelar. */
    public function cancel(Usuario $user, Orden $orden): bool
    {
        return (bool) $user->activo
            && $user->rol?->nombre === 'administrador';
    }

    public function delete(Usuario $user, Orden $orden): bool
    {
        return (bool) $user->activo
            && $user->rol?->nombre === 'administrador';
    }
}