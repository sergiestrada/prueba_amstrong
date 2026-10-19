<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrdenMantenimiento extends Model
{
    protected $table = 'ordenes_mantenimiento';

    protected $fillable = [
        'vehiculo_id', 'usuario_id', 'tipo', 'descripcion',
        'odometro_ingreso', 'fecha_programada', 'fecha_cierre',
        'costo', 'estatus',
    ];

    protected function casts(): array
    {
        return [
            'odometro_ingreso' => 'integer',
            'fecha_programada' => 'date',
            'fecha_cierre' => 'date',
            'costo' => 'decimal:2',
        ];
    }

    public const TIPOS = ['Preventivo', 'Correctivo'];
    public const ESTATUS = ['Abierta', 'En proceso', 'Cerrada', 'Cancelada'];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function mantenimientoHistorial(): HasOne
    {
        return $this->hasOne(MantenimientoHistorial::class, 'orden_id');
    }

    public function estaBloqueadaParaEdicion(): bool
    {
        return $this->estatus === 'Cerrada';
    }
}
