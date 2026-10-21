<?php

namespace App\Http\Requests\Orden;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrdenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización real se valida en OrdenController::update
    }

    public function rules(): array
    {
        return [
            'vehiculo_id'      => ['sometimes', 'integer', 'exists:vehiculos,id'],
            'tipo'             => ['sometimes', 'in:preventivo,correctivo'],
            'descripcion'      => ['sometimes', 'string', 'max:2000'],
            'odometro_ingreso' => ['sometimes', 'integer', 'min:0'],
            'fecha_programada' => ['sometimes', 'date'],
            'prioridad'        => ['sometimes', 'in:baja,media,alta'],
            'costo_estimado'   => ['sometimes', 'numeric', 'min:0', 'max:99999999.99'],
            'asignado_a_id'    => ['sometimes', 'nullable', 'integer', 'exists:usuarios,id'],
            'notas'            => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}