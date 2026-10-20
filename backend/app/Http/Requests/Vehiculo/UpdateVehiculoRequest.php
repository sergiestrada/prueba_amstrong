<?php

namespace App\Http\Requests\Vehiculo;

use App\Models\Vehiculo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vehiculo = $this->route('vehiculo');
        return $vehiculo instanceof Vehiculo
            && ($this->user()?->can('update', $vehiculo) ?? false);
    }

    public function rules(): array
    {
        $vehiculo = $this->route('vehiculo');
        $id       = $vehiculo instanceof Vehiculo ? $vehiculo->id : null;
        $maxYear  = (int) date('Y') + 1;

        return [
            'placa'                   => ['sometimes', 'string', 'max:20',
                                          Rule::unique('vehiculos', 'placa')->ignore($id)],
            'marca'                   => ['sometimes', 'string', 'max:80'],
            'modelo'                  => ['sometimes', 'string', 'max:80'],
            'anio'                    => ['sometimes', 'integer', "between:1900,{$maxYear}"],
            'color'                   => ['nullable', 'string', 'max:50'],
            'vin'                     => ['nullable', 'string', 'size:17',
                                          Rule::unique('vehiculos', 'vin')->ignore($id)],
            'tipo'                    => ['sometimes', 'in:sedan,suv,pickup,camion,moto,otro'],
            'kilometraje'             => ['sometimes', 'integer', 'min:0'],
            'fecha_ultimo_servicio'   => ['nullable', 'date', 'before_or_equal:today'],
            'fecha_proximo_servicio'  => ['nullable', 'date', 'after_or_equal:fecha_ultimo_servicio'],
            'estado'                  => ['sometimes', 'in:activo,mantenimiento,inactivo'],
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