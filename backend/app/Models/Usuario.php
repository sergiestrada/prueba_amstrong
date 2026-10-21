<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = ['nombre', 'email', 'password', 'rol_id', 'activo'];

    protected $hidden = ['password', 'remember_token'];
   

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo'   => 'boolean',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    /** Órdenes creadas por este usuario. */
    public function ordenes(): HasMany
    {
        return $this->hasMany(Orden::class, 'creado_por_id');
    }

    /** Órdenes asignadas a este usuario. */
    public function ordenesAsignadas(): HasMany
    {
        return $this->hasMany(Orden::class, 'asignado_a_id');
    }

    public function historialEstatus(): HasMany
    {
        return $this->hasMany(VehiculoEstatusHistorial::class, 'usuario_id');
    }

    public function esAdministrador(): bool
    {
        return $this->rol?->nombre === Rol::ADMINISTRADOR;
    }

    public function esCapturista(): bool
    {
        return $this->rol?->nombre === Rol::CAPTURISTA;
    }
}