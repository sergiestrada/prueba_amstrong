<?php

namespace App\Http\Resources;

use App\Models\Orden;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrdenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'folio'                    => $this->folio,
            'tipo'                     => $this->tipo,
            'status'                   => $this->status,
            'prioridad'                => $this->prioridad,
            'transiciones_disponibles' => Orden::TRANSICIONES[$this->status] ?? [],

            'vehiculo_id' => $this->vehiculo_id,
            'vehiculo'    => $this->whenLoaded('vehiculo', fn () => $this->vehiculo ? [
                'id'               => $this->vehiculo->id,
                'placa'            => $this->vehiculo->placa,
                'numero_economico' => $this->vehiculo->numero_economico,
                'marca'            => $this->vehiculo->marca,
                'modelo'           => $this->vehiculo->modelo,
                'anio'             => $this->vehiculo->anio,
            ] : null),

            'creado_por' => $this->whenLoaded('creadoPor', fn () => $this->creadoPor ? [
                'id'     => $this->creadoPor->id,
                'nombre' => $this->creadoPor->nombre,
                'email'  => $this->creadoPor->email,
            ] : null),

            'asignado_a' => $this->whenLoaded('asignadoA', fn () => $this->asignadoA ? [
                'id'     => $this->asignadoA->id,
                'nombre' => $this->asignadoA->nombre,
                'email'  => $this->asignadoA->email,
            ] : null),

            'descripcion'      => $this->descripcion,
            'odometro_ingreso' => $this->odometro_ingreso,
            'fecha_programada' => $this->fecha_programada?->toDateString(),
            'fecha_inicio'     => $this->fecha_inicio?->toIso8601String(),
            'fecha_cierre'     => $this->fecha_cierre?->toDateString(),

            'costo'          => $this->costo,
            'costo_estimado' => $this->costo_estimado,
            'costo_real'     => $this->costo_real,

            'notas'      => $this->notas,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}