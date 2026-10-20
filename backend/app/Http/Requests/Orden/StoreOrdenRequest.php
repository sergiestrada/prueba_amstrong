<?php

namespace App\Http\Requests\Orden;

use App\Models\Orden;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrdenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Orden::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'vehiculo_id'         => ['required', 'integer', 'exists:vehiculos,id'],
            'asignado_a_id'       => ['nullable', 'integer', 'exists:usuarios,id'],
            'tipo'                => ['required', 'in:preventivo,correctivo,emergencia'],
            'prioridad'           => ['required', 'in:baja,media,alta,critica'],
            'descripcion'         => ['required', 'string', 'max:2000'],
            'notas'               => ['nullable', 'string', 'max:2000'],
            'fecha_programada'    => ['nullable', 'date'],
            'costo_estimado'      => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'kilometraje_ingreso' => ['nullable', 'integer', 'min:0'],
        ];
    }
}