<?php

namespace App\Http\Requests\Vehiculo;

use App\Models\Vehiculo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehiculoRequest extends FormRequest
{
   public function authorize(): bool
    {
        return true;   
    }

    public function rules(): array
    {
        $vehiculo = $this->route('vehiculo');
        $id       = $vehiculo instanceof Vehiculo ? $vehiculo->id : null;
        $maxYear  = (int) date('Y') + 1;

        return [
            'placa'            => ['sometimes', 'string', 'max:20',
                                    Rule::unique('vehiculos', 'placa')->ignore($id)],
            'numero_economico' => ['sometimes', 'string', 'max:30',
                                    Rule::unique('vehiculos', 'numero_economico')->ignore($id)],
            'marca'            => ['sometimes', 'string', 'max:80'],
            'modelo'           => ['sometimes', 'string', 'max:80'],
            'anio'             => ['sometimes', 'integer', "between:1900,{$maxYear}"],
            'odometro_actual'  => ['sometimes', 'integer', 'min:0'],
            'estatus'          => ['sometimes', 'in:activo,en_taller,baja'],
            'fecha_alta'       => ['nullable', 'date'],
            'notas'            => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('placa')) {
            $this->merge(['placa' => strtoupper(trim((string) $this->placa))]);
        }
        if ($this->has('numero_economico')) {
            $this->merge(['numero_economico' => strtoupper(trim((string) $this->numero_economico))]);
        }
    }
}