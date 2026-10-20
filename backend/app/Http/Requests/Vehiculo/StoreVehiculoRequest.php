<?php

namespace App\Http\Requests\Vehiculo;

use App\Models\Vehiculo;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Vehiculo::class) ?? false;
    }

    public function rules(): array
    {
        $maxYear = (int) date('Y') + 1;

        return [
            'placa'                   => ['required', 'string', 'max:20', 'unique:vehiculos,placa'],
            'marca'                   => ['required', 'string', 'max:80'],
            'modelo'                  => ['required', 'string', 'max:80'],
            'anio'                    => ['required', 'integer', "between:1900,{$maxYear}"],
            'color'                   => ['nullable', 'string', 'max:50'],
            'vin'                     => ['nullable', 'string', 'size:17', 'unique:vehiculos,vin'],
            'tipo'                    => ['required', 'in:sedan,suv,pickup,camion,moto,otro'],
            'kilometraje'             => ['required', 'integer', 'min:0'],
            'fecha_ultimo_servicio'   => ['nullable', 'date', 'before_or_equal:today'],
            'fecha_proximo_servicio'  => ['nullable', 'date', 'after_or_equal:fecha_ultimo_servicio'],
            'estado'                  => ['required', 'in:activo,mantenimiento,inactivo'],
            'activo'                  => ['sometimes', 'boolean'],
            'notas'                   => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('placa')) {
            $this->merge(['placa' => strtoupper(trim((string) $this->placa))]);
        }
        if ($this->has('vin') && $this->vin !== null) {
            $this->merge(['vin' => strtoupper(trim((string) $this->vin))]);
        }
    }
}