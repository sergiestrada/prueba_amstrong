<?php

namespace App\Http\Requests\Orden;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrdenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización real se valida en OrdenController::store
    }

    public function rules(): array
    {
        return [
            'vehiculo_id'      => ['required', 'integer', 'exists:vehiculos,id'],
            'tipo'             => ['required', 'in:preventivo,correctivo'],
            'descripcion'      => ['required', 'string', 'max:2000'],
            'odometro_ingreso' => ['required', 'integer', 'min:0'],
            'fecha_programada' => ['required', 'date'],
            'prioridad'        => ['nullable', 'in:baja,media,alta'],
            'costo'   => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'notas'            => ['nullable', 'string', 'max:2000'],
        ];
    }
}