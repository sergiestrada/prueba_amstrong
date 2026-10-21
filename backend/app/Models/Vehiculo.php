<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehiculo extends Model
{
    use HasFactory, SoftDeletes;

    public const ESTATUS_ACTIVO    = 'activo';
    public const ESTATUS_EN_TALLER = 'en_taller';
    public const ESTATUS_BAJA      = 'baja';

    public const KM_LIMITE   = 10000; // RN-07
    public const DIAS_LIMITE = 180;   // RN-07

    protected $table = 'vehiculos';

    protected $fillable = [
        'placa',
        'numero_economico',
        'marca',
        'modelo',
        'anio',
        'odometro_actual',
        'estatus',
        'fecha_alta',
        'notas',
    ];

    protected $casts = [
        'anio'            => 'integer',
        'odometro_actual' => 'integer',
        'fecha_alta'      => 'date',
    ];

    public function ordenes()
    {
        return $this->hasMany(Orden::class, 'vehiculo_id');
    }

    /** Último preventivo CERRADO del vehículo. */
    public function ultimoPreventivoCerrado(): ?Orden
    {
        return $this->ordenes()
            ->where('tipo', 'preventivo')
            ->where('status', Orden::STATUS_CERRADA)          // ← status
            ->orderByDesc('fecha_cierre')
            ->first();
    }

    public function tieneOrdenActiva(): bool
    {
        return $this->ordenes()
            ->whereIn('status', [Orden::STATUS_ABIERTA, Orden::STATUS_EN_PROCESO]) // ← status
            ->exists();
    }
    /** RN-07 / RF-05: requiere servicio preventivo. */
    public function requiereServicio(): bool
    {
        $ultimo = $this->ultimoPreventivoCerrado();

        $referenciaFecha = $ultimo?->fecha_cierre ?? $this->fecha_alta;
        $referenciaOdo   = $ultimo?->odometro_ingreso ?? 0;

        $kmDesde   = $this->odometro_actual - $referenciaOdo;
        $diasDesde = $referenciaFecha?->diffInDays(now()) ?? 0;

        return $kmDesde > self::KM_LIMITE || $diasDesde > self::DIAS_LIMITE;
    }

    /** Scope: vehículos que requieren servicio (para GET /vehicles/due-for-service). */
      /** Scope: vehículos que requieren servicio (para GET /vehicles/due-for-service). */
    public function scopeDueForService(Builder $query): Builder
    {
        return $query->where('estatus', '!=', self::ESTATUS_BAJA)
            ->where(function (Builder $q) {
                // Caso A: tiene preventivo cerrado y excede km o días
                $q->whereExists(function ($sub) {
                    $sub->selectRaw('1')
                        ->from('ordenes as ult')
                        ->whereColumn('ult.vehiculo_id', 'vehiculos.id')
                        ->where('ult.tipo', 'preventivo')
                        ->where('ult.status', Orden::STATUS_CERRADA)   // ← status
                        ->where(function ($inner) {
                            $inner->whereRaw(
                                'vehiculos.odometro_actual - ult.odometro_ingreso > ?',
                                [self::KM_LIMITE]
                            )->orWhereRaw(
                                'DATEDIFF(NOW(), ult.fecha_cierre) > ?',
                                [self::DIAS_LIMITE]
                            );
                        });
                })
                // Caso B: sin preventivo cerrado → usar fecha_alta y odómetro desde 0
                ->orWhere(function ($noHist) {
                    $noHist->whereNotExists(function ($sub) {
                        $sub->selectRaw('1')
                            ->from('ordenes')
                            ->whereColumn('ordenes.vehiculo_id', 'vehiculos.id')
                            ->where('ordenes.tipo', 'preventivo')
                            ->where('ordenes.status', Orden::STATUS_CERRADA); // ← status
                    })
                    ->where(function ($fallback) {
                        $fallback->where('vehiculos.odometro_actual', '>', self::KM_LIMITE)
                            ->orWhereRaw(
                                'DATEDIFF(NOW(), vehiculos.fecha_alta) > ?',
                                [self::DIAS_LIMITE]
                            );
                    });
                });
            });
    }
}
