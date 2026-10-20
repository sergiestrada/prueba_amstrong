<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehiculoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $diasParaServicio = null;

        if ($this->fecha_proximo_servicio) {
            $diasParaServicio = (int) now()
                ->startOfDay()
                ->diffInDays($this->fecha_proximo_servicio->startOfDay(), false);
        }

        return [
            'id'                      => $this->id,
            'placa'                   => $this->placa,
            'marca'                   => $this->marca,
            'modelo'                  => $this->modelo,
            'anio'                    => $this->anio,
            'color'                   => $this->color,
            'vin'                     => $this->vin,
            'tipo'                    => $this->tipo,
            'kilometraje'             => $this->kilometraje,
            'fecha_ultimo_servicio'   => $this->fecha_ultimo_servicio?->toDateString(),
            'fecha_proximo_servicio'  => $this->fecha_proximo_servicio?->toDateString(),
            'dias_para_servicio'      => $diasParaServicio,
            'estado'                  => $this->estado,
            'activo'                  => $this->activo,
            'notas'                   => $this->notas,
            'created_at'              => $this->created_at?->toIso8601String(),
            'updated_at'              => $this->updated_at?->toIso8601String(),
            'deleted_at'              => $this->deleted_at?->toIso8601String(),
        ];
    }
}