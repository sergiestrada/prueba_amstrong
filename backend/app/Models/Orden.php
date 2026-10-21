<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Orden extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ABIERTA    = 'abierta';
    public const STATUS_EN_PROCESO = 'en_proceso';
    public const STATUS_CERRADA    = 'cerrada';
    public const STATUS_CANCELADA  = 'cancelada';

    public const TRANSICIONES = [
        self::STATUS_ABIERTA    => [self::STATUS_EN_PROCESO, self::STATUS_CANCELADA],
        self::STATUS_EN_PROCESO => [self::STATUS_CERRADA,    self::STATUS_CANCELADA],
        self::STATUS_CERRADA    => [],
        self::STATUS_CANCELADA  => [],
    ];

    protected const FOLIO_CODES        = ['preventivo' => 'PRE', 'correctivo' => 'COR'];
    protected const FOLIO_DEFAULT_CODE = 'ORD';
    protected const FOLIO_INCLUDE_YEAR = true;
    protected const FOLIO_SEQ_DIGITS   = 4;

    protected $table = 'ordenes';

    protected $fillable = [
        'folio',
        'vehiculo_id',
        'creado_por_id',
        'asignado_a_id',
        'tipo',
        'status',            // ← status
        'prioridad',
        'descripcion',
        'odometro_ingreso',
        'fecha_programada',
        'fecha_inicio',
        'fecha_cierre',
        'costo',
        'costo_estimado',
        'costo_real',
        'notas',
    ];

    protected $casts = [
        'fecha_programada' => 'date',
        'fecha_inicio'     => 'datetime',
        'fecha_cierre'     => 'date',
        'costo'            => 'decimal:2',
        'costo_estimado'   => 'decimal:2',
        'costo_real'       => 'decimal:2',
        'odometro_ingreso' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $orden) {
            if (empty($orden->folio)) {
                $orden->folio = static::nextFolio($orden->tipo);
            }
        });
    }

    public static function folioCode(?string $tipo): string
    {
        return self::FOLIO_CODES[$tipo ?? ''] ?? self::FOLIO_DEFAULT_CODE;
    }

    public static function nextFolio(?string $tipo = null): string
    {
        $code   = self::folioCode($tipo);
        $prefix = self::FOLIO_INCLUDE_YEAR
            ? sprintf('%s-%d-', $code, (int) now()->format('Y'))
            : sprintf('%s-', $code);

        $last = static::withTrashed()
            ->where('folio', 'like', "{$prefix}%")
            ->orderByDesc('folio')
            ->lockForUpdate()
            ->value('folio');

        $next = $last ? ((int) substr($last, -self::FOLIO_SEQ_DIGITS)) + 1 : 1;

        return $prefix . str_pad((string) $next, self::FOLIO_SEQ_DIGITS, '0', STR_PAD_LEFT);
    }

    // ── Relaciones ────────────────────────────────────────────────
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

    // ── Reglas de negocio ─────────────────────────────────────────
    public function canTransitionTo(string $nuevo): bool
    {
        return in_array($nuevo, self::TRANSICIONES[$this->status] ?? [], true);
    }

    public function estaCerrada(): bool
    {
        return in_array($this->status, [self::STATUS_CERRADA, self::STATUS_CANCELADA], true);
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