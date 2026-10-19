<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Vehiculo extends Model
{
    protected $table = 'vehiculos';

    protected $fillable = [
        'placa', 'numero_economico', 'marca', 'modelo',
        'anio', 'odometro_actual', 'estatus', 'fecha_alta',
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'odometro_actual' => 'integer',
            'fecha_alta' => 'date',
        ];
    }

    public const ESTATUS = ['Activo', 'En taller', 'Baja'];

    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenMantenimiento::class, 'vehiculo_id');
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(MantenimientoHistorial::class, 'vehiculo_id');
    }

    public function historialEstatus(): HasMany
    {
        return $this->hasMany(VehiculoEstatusHistorial::class, 'vehiculo_id');
    }

    public function requiereServicio(): bool
    {
        $ultimoPreventivo = $this->mantenimientos()
            ->where('tipo', 'Preventivo')
            ->orderByDesc('fecha')
            ->first();

        $fechaReferencia = $ultimoPreventivo?->fecha ?? $this->fecha_alta;
        $odometroReferencia = $ultimoPreventivo?->odometro ?? 0;

        return $this->odometro_actual - $odometroReferencia > 10000
            || $fechaReferencia->copy()->addDays(180)->lt(now()->startOfDay());
    }
}
