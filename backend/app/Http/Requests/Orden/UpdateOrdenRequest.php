<?php

namespace App\Http\Requests\Orden;

use App\Models\Orden;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrdenRequest extends FormRequest
{
    public function authorize(): bool
    {
        $orden = $this->route('orden');
        return $orden instanceof Orden
            && ($this->user()?->can('update', $orden) ?? false);
    }

    public function rules(): array
    {
        return [
            'vehiculo_id'         => ['sometimes', 'integer', 'exists:vehiculos,id'],
            'asignado_a_id'       => ['nullable', 'integer', 'exists:usuarios,id'],
            'tipo'                => ['sometimes', 'in:preventivo,correctivo,emergencia'],
            'prioridad'           => ['sometimes', 'in:baja,media,alta,critica'],
            'descripcion'         => ['sometimes', 'string', 'max:2000'],
            'notas'               => ['nullable', 'string', 'max:2000'],
            'fecha_programada'    => ['nullable', 'date'],
            'costo_estimado'      => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'kilometraje_ingreso' => ['nullable', 'integer', 'min:0'],
            // status NO se cambia por aquí — usa PATCH /orders/{id}/status
        ];
    }

    public function messages(): array
    {
        return [
            'status.prohibited' => 'Para cambiar el estatus usa PATCH /api/v1/orders/{id}/status.',
        ];
    }
}