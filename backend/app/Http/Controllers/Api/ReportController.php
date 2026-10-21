<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Orden;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function costs(Request $request): JsonResponse
    {
       // $this->authorize('viewAny', Orden::class);

        $validated = $request->validate([
            'from'              => ['nullable', 'date'],
            'to'                => ['nullable', 'date'],
            'vehiculo_id'       => ['nullable', 'integer', 'exists:vehiculos,id'],
            'status'            => ['nullable', 'in:pendiente,en_progreso,completada,cancelada'],
            'include_cancelled' => ['nullable', 'boolean'],
            'date_field'        => ['nullable', 'in:created_at,fecha_cierre,fecha_inicio,fecha_programada'],
        ]);

        if (
            !empty($validated['from']) &&
            !empty($validated['to']) &&
            $validated['to'] < $validated['from']
        ) {
            throw ValidationException::withMessages([
                'to' => ['La fecha "to" debe ser igual o posterior a "from".'],
            ]);
        }

        $from             = $validated['from']  ?? null;
        $to               = $validated['to']    ?? null;
        $vehiculoId       = $validated['vehiculo_id'] ?? null;
        $status           = $validated['status'] ?? null;
        $includeCancelled = (bool) ($validated['include_cancelled'] ?? false);
        $dateField        = $validated['date_field'] ?? 'created_at';

        $query = Orden::query()
            ->join('vehiculos', 'vehiculos.id', '=', 'ordenes.vehiculo_id')
            ->when($from, fn ($q) => $q->whereDate("ordenes.{$dateField}", '>=', $from))
            ->when($to,   fn ($q) => $q->whereDate("ordenes.{$dateField}", '<=', $to))
            ->when($vehiculoId, fn ($q) => $q->where('ordenes.vehiculo_id', $vehiculoId))
            ->when($status, fn ($q) => $q->where('ordenes.status', $status))
            // Por defecto excluye canceladas (no tienen costo real útil)
            ->when(
                ! $status && ! $includeCancelled,
                fn ($q) => $q->where('ordenes.status', '!=', Orden::STATUS_CANCELADA)
            );

        // ── Totales globales
        $totals = (clone $query)
            ->selectRaw('COUNT(*) as ordenes')
            ->selectRaw('COALESCE(SUM(ordenes.costo_estimado), 0) as costo_estimado')
            ->selectRaw('COALESCE(SUM(ordenes.costo_real), 0) as costo_real')
            ->first();

        // ── Por tipo de mantenimiento
        $porTipo = (clone $query)
            ->select('ordenes.tipo')
            ->selectRaw('COUNT(*) as ordenes')
            ->selectRaw('COALESCE(SUM(ordenes.costo_estimado), 0) as costo_estimado')
            ->selectRaw('COALESCE(SUM(ordenes.costo_real), 0) as costo_real')
            ->groupBy('ordenes.tipo')
            ->orderBy('ordenes.tipo')
            ->get()
            ->map(fn ($r) => [
                'tipo'            => $r->tipo,
                'ordenes'         => (int) $r->ordenes,
                'costo_estimado'  => round((float) $r->costo_estimado, 2),
                'costo_real'      => round((float) $r->costo_real, 2),
            ])
            ->values();

        // ── Por vehículo (con desglose por tipo dentro de cada uno)
        $rows = (clone $query)
            ->select(
                'ordenes.vehiculo_id',
                'vehiculos.placa',
                'vehiculos.marca',
                'vehiculos.modelo',
                'vehiculos.anio',
                'ordenes.tipo'
            )
            ->selectRaw('COUNT(*) as ordenes')
            ->selectRaw('COALESCE(SUM(ordenes.costo_estimado), 0) as costo_estimado')
            ->selectRaw('COALESCE(SUM(ordenes.costo_real), 0) as costo_real')
            ->groupBy(
                'ordenes.vehiculo_id',
                'vehiculos.placa',
                'vehiculos.marca',
                'vehiculos.modelo',
                'vehiculos.anio',
                'ordenes.tipo'
            )
            ->get();

        $porVehiculo = [];
        foreach ($rows as $row) {
            $vid = $row->vehiculo_id;

            if (! isset($porVehiculo[$vid])) {
                $porVehiculo[$vid] = [
                    'vehiculo_id'    => $vid,
                    'placa'          => $row->placa,
                    'marca'          => $row->marca,
                    'modelo'         => $row->modelo,
                    'anio'           => (int) $row->anio,
                    'ordenes'        => 0,
                    'costo_estimado' => 0.0,
                    'costo_real'     => 0.0,
                    'por_tipo'       => [],
                ];
            }

            $porVehiculo[$vid]['ordenes']        += (int) $row->ordenes;
            $porVehiculo[$vid]['costo_estimado'] += (float) $row->costo_estimado;
            $porVehiculo[$vid]['costo_real']     += (float) $row->costo_real;

            $porVehiculo[$vid]['por_tipo'][$row->tipo] = [
                'ordenes'        => (int) $row->ordenes,
                'costo_estimado' => round((float) $row->costo_estimado, 2),
                'costo_real'     => round((float) $row->costo_real, 2),
            ];
        }

        // Ordenar por costo real descendente
        usort($porVehiculo, fn ($a, $b) => $b['costo_real'] <=> $a['costo_real']);

        foreach ($porVehiculo as &$v) {
            $v['costo_estimado'] = round($v['costo_estimado'], 2);
            $v['costo_real']     = round($v['costo_real'], 2);
        }
        unset($v);

        return response()->json([
            'data' => [
                'period' => [
                    'from'       => $from,
                    'to'         => $to,
                    'date_field' => $dateField,
                ],
                'filters' => [
                    'vehiculo_id'       => $vehiculoId,
                    'status'            => $status,
                    'include_cancelled' => $includeCancelled,
                ],
                'totals' => [
                    'ordenes'        => (int) $totals->ordenes,
                    'costo_estimado' => round((float) $totals->costo_estimado, 2),
                    'costo_real'     => round((float) $totals->costo_real, 2),
                ],
                'por_tipo'     => $porTipo,
                'por_vehiculo' => array_values($porVehiculo),
            ],
        ]);
    }
}