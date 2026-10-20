<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Orden extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDIENTE   = 'pendiente';
    public const STATUS_EN_PROGRESO = 'en_progreso';
    public const STATUS_COMPLETADA  = 'completada';
    public const STATUS_CANCELADA   = 'cancelada';

    /** Transiciones permitidas por estado. */
    public const TRANSICIONES = [
        self::STATUS_PENDIENTE   => [self::STATUS_EN_PROGRESO, self::STATUS_CANCELADA],
        self::STATUS_EN_PROGRESO => [self::STATUS_COMPLETADA, self::STATUS_CANCELADA],
        self::STATUS_COMPLETADA  => [],
        self::STATUS_CANCELADA   => [],
    ];

    protected $table = 'ordenes';

    protected $fillable = [
        'folio', 'vehiculo_id', 'creado_por_id', 'asignado_a_id',
        'tipo', 'prioridad', 'status', 'descripcion', 'notas',
        'fecha_programada', 'fecha_inicio', 'fecha_cierre',
        'costo_estimado', 'costo_real', 'kilometraje_ingreso',
    ];

    protected $casts = [
        'fecha_programada'    => 'date',
        'fecha_inicio'        => 'datetime',
        'fecha_cierre'        => 'datetime',
        'costo_estimado'      => 'decimal:2',
        'costo_real'          => 'decimal:2',
        'kilometraje_ingreso' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $orden) {
            if (empty($orden->folio)) {
                $orden->folio = static::nextFolio();
            }
        });
    }

    /**
     * Folio con formato ORD-YYYY-0001.
     * Debe llamarse dentro de una transacción (usa lockForUpdate).
     */
    public static function nextFolio(): string
    {
        $year   = (int) now()->format('Y');
        $prefix = "ORD-{$year}-";

        $last = static::withTrashed()
            ->where('folio', 'like', "{$prefix}%")
            ->orderByDesc('folio')
            ->lockForUpdate()
            ->value('folio');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('%s%04d', $prefix, $next);
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(Usuario::class, 'creado_por_id');
    }

    public function asignadoA()
    {
        return $this->belongsTo(Usuario::class, 'asignado_a_id');
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::TRANSICIONES[$this->status] ?? [], true);
    }

    public function estaCerrada(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETADA, self::STATUS_CANCELADA], true);
    }

    public function scopeStatus(Builder $q, ?string $status): Builder
    {
        return $status ? $q->where('status', $status) : $q;
    }

    public function scopePrioridad(Builder $q, ?string $prioridad): Builder
    {
        return $prioridad ? $q->where('prioridad', $prioridad) : $q;
    }
}