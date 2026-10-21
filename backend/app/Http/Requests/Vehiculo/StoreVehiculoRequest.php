<?php

namespace App\Http\Requests\Vehiculo;

use App\Models\Vehiculo;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   
    }

    public function rules(): array
    {
        $maxYear = (int) date('Y') + 1;

        return [
            'placa'            => ['required', 'string', 'max:20', 'unique:vehiculos,placa'],
            'numero_economico' => ['required', 'string', 'max:30', 'unique:vehiculos,numero_economico'],
            'marca'            => ['required', 'string', 'max:80'],
            'modelo'           => ['required', 'string', 'max:80'],
            'anio'             => ['required', 'integer', "between:1900,{$maxYear}"],
            'odometro_actual'  => ['required', 'integer', 'min:0'],
            'estatus'          => ['required', 'in:activo,en_taller,baja'],
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
