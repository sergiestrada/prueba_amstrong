<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehiculo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehiculos';

    protected $fillable = [
        'placa',
        'marca',
        'modelo',
        'anio',
        'color',
        'vin',
        'tipo',
        'kilometraje',
        'fecha_ultimo_servicio',
        'fecha_proximo_servicio',
        'estado',
        'activo',
        'notas',
    ];

    protected $casts = [
        'anio'                    => 'integer',
        'kilometraje'             => 'integer',
        'fecha_ultimo_servicio'   => 'date',
        'fecha_proximo_servicio'  => 'date',
        'activo'                  => 'boolean',
    ];

    /** Vehículos que requieren servicio en los próximos N días. */
    public function scopeDueForService(Builder $query, int $daysAhead = 7): Builder
    {
        return $query
            ->whereNotNull('fecha_proximo_servicio')
            ->whereDate(
                'fecha_proximo_servicio',
                '<=',
                now()->addDays($daysAhead)->toDateString()
            );
    }
}