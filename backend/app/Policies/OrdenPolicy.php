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
            && ($user->esAdministrador() || $user->esCapturista());
    }

    public function update(Usuario $user, Orden $orden): bool
    {
        if (! $user->activo) return false;
        if ($orden->estaCerrada()) return false;
        return $user->esAdministrador() || $user->esCapturista();
    }

    public function changeStatus(Usuario $user, Orden $orden): bool
    {
        return (bool) $user->activo
            && ($user->esAdministrador() || $user->esCapturista());
    }

    public function cancel(Usuario $user, Orden $orden): bool
    {
        return (bool) $user->activo && $user->esAdministrador();
    }

    public function delete(Usuario $user, Orden $orden): bool
    {
        return (bool) $user->activo && $user->esAdministrador();
    }
}