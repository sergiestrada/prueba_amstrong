<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MantenimientoHistorial extends Model
{
    protected $table = 'mantenimientos_historial';

    protected $fillable = [
        'vehiculo_id', 'orden_id', 'tipo', 'odometro', 'fecha', 'costo',
    ];

    protected function casts(): array
    {
        return [
            'odometro' => 'integer',
            'fecha'    => 'date',
            'costo'    => 'decimal:2',
        ];
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function orden(): BelongsTo
    {
        return $this->belongsTo(Orden::class, 'orden_id');
    }
}