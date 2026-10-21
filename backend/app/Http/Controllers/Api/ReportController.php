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
        $validated = $request->validate([
            'from'              => ['nullable', 'date'],
            'to'                => ['nullable', 'date'],
            'vehiculo_id'       => ['nullable', 'integer', 'exists:vehiculos,id'],
            'status'            => ['nullable', 'in:abierta,en_proceso,cerrada,cancelada'],
            'include_cancelled' => ['nullable', 'in:0,1'],
            'date_field'        => ['nullable', 'in:created_at,fecha_cierre,fecha_inicio,fecha_programada'],
        ]);

        if (
            ! empty($validated['from']) &&
            ! empty($validated['to']) &&
            $validated['to'] < $validated['from']
        ) {
            throw ValidationException::withMessages([
                'to' => ['La fecha "to" debe ser igual o posterior a "from".'],
            ]);
        }

        $from             = $validated['from'] ?? null;
        $to               = $validated['to'] ?? null;
        $vehiculoId       = $validated['vehiculo_id'] ?? null;
        $status           = $validated['status'] ?? null;
        $includeCancelled = (bool) ($validated['include_cancelled'] ?? false);
        $dateField        = $validated['date_field'] ?? 'created_at';

        $costoExpr = 'COALESCE(ordenes.costo_real, ordenes.costo, 0)';

        $query = Orden::query()
            ->join('vehiculos', 'vehiculos.id', '=', 'ordenes.vehiculo_id')
            ->when($from, fn ($q) => $q->whereDate("ordenes.{$dateField}", '>=', $from))
            ->when($to, fn ($q) => $q->whereDate("ordenes.{$dateField}", '<=', $to))
            ->when($vehiculoId, fn ($q) => $q->where('ordenes.vehiculo_id', $vehiculoId))
            ->when($status, fn ($q) => $q->where('ordenes.status', $status))
            ->when(
                ! $status && ! $includeCancelled,
                fn ($q) => $q->where('ordenes.status', '!=', Orden::STATUS_CANCELADA)
            );

        $totals = (clone $query)
            ->selectRaw('COUNT(*) as ordenes')
            ->selectRaw("COALESCE(SUM({$costoExpr}), 0) as costo")
            ->first();

        $porTipo = (clone $query)
            ->select('ordenes.tipo')
            ->selectRaw('COUNT(*) as ordenes')
            ->selectRaw("COALESCE(SUM({$costoExpr}), 0) as costo")
            ->groupBy('ordenes.tipo')
            ->orderBy('ordenes.tipo')
            ->get()
            ->map(fn ($r) => [
                'tipo'    => $r->tipo,
                'ordenes' => (int) $r->ordenes,
                'costo'   => round((float) $r->costo, 2),
            ])
            ->values();

        $rows = (clone $query)
            ->select(
                'ordenes.vehiculo_id',
                'vehiculos.placa',
                'vehiculos.numero_economico',
                'vehiculos.marca',
                'vehiculos.modelo',
                'vehiculos.anio',
                'ordenes.tipo'
            )
            ->selectRaw('COUNT(*) as ordenes')
            ->selectRaw("COALESCE(SUM({$costoExpr}), 0) as costo")
            ->groupBy(
                'ordenes.vehiculo_id',
                'vehiculos.placa',
                'vehiculos.numero_economico',
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
                    'vehiculo_id'      => $vid,
                    'placa'            => $row->placa,
                    'numero_economico' => $row->numero_economico,
                    'marca'            => $row->marca,
                    'modelo'           => $row->modelo,
                    'anio'             => (int) $row->anio,
                    'ordenes'          => 0,
                    'costo'            => 0.0,
                    'por_tipo'         => [],
                ];
            }

            $porVehiculo[$vid]['ordenes'] += (int) $row->ordenes;
            $porVehiculo[$vid]['costo']   += (float) $row->costo;

            $porVehiculo[$vid]['por_tipo'][$row->tipo] = [
                'ordenes' => (int) $row->ordenes,
                'costo'   => round((float) $row->costo, 2),
            ];
        }

        usort($porVehiculo, fn ($a, $b) => $b['costo'] <=> $a['costo']);

        foreach ($porVehiculo as &$v) {
            $v['costo'] = round($v['costo'], 2);
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
                    'ordenes' => (int) $totals->ordenes,
                    'costo'   => round((float) $totals->costo, 2),
                ],
                'por_tipo'     => $porTipo,
                'por_vehiculo' => array_values($porVehiculo),
            ],
        ]);
    }
}