<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehiculoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'placa'             => $this->placa,
            'numero_economico'  => $this->numero_economico,
            'marca'             => $this->marca,
            'modelo'            => $this->modelo,
            'anio'              => $this->anio,
            'odometro_actual'   => $this->odometro_actual,
            'estatus'           => $this->estatus,
            'fecha_alta'        => $this->fecha_alta?->toDateString(),
            'notas'             => $this->notas,
            'requiere_servicio' => $this->requiereServicio(),
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
            'deleted_at'        => $this->deleted_at?->toIso8601String(),
        ];
    }
}