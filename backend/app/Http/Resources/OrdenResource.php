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
            'id'          => $this->id,
            'folio'       => $this->folio,
            'status'      => $this->status,
            'tipo'        => $this->tipo,
            'prioridad'   => $this->prioridad,

            // Transiciones válidas desde el estado actual (para pintar botones en el front)
            'transiciones_disponibles' => Orden::TRANSICIONES[$this->status] ?? [],

            'vehiculo_id' => $this->vehiculo_id,
            'vehiculo'    => $this->whenLoaded('vehiculo', fn () => [
                'id'     => $this->vehiculo->id,
                'placa'  => $this->vehiculo->placa,
                'marca'  => $this->vehiculo->marca,
                'modelo' => $this->vehiculo->modelo,
                'anio'   => $this->vehiculo->anio,
            ]),

            'creado_por'  => $this->whenLoaded('creadoPor', fn () => [
                'id'     => $this->creadoPor->id,
                'nombre' => $this->creadoPor->nombre,
                'email'  => $this->creadoPor->email,
            ]),

            'asignado_a'  => $this->whenLoaded('asignadoA', fn () => $this->asignadoA ? [
                'id'       => $this->asignadoA->id,
                'nombre'   => $this->asignadoA->nombre,
                'apellido' => $this->asignadoA->apellido,
            ] : null),

            'descripcion'         => $this->descripcion,
            'notas'               => $this->notas,
            'fecha_programada'    => $this->fecha_programada?->toDateString(),
            'fecha_inicio'        => $this->fecha_inicio?->toIso8601String(),
            'fecha_cierre'        => $this->fecha_cierre?->toIso8601String(),
            'costo_estimado'      => $this->costo_estimado,
            'costo_real'          => $this->costo_real,
            'kilometraje_ingreso' => $this->kilometraje_ingreso,

            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),
            'deleted_at'          => $this->deleted_at?->toIso8601String(),
        ];
    }
}