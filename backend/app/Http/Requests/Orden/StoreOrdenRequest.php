<?php

namespace App\Http\Requests\Orden;

use App\Models\Orden;
use App\Models\Vehiculo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOrdenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Orden::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'vehiculo_id'      => ['required', 'integer', 'exists:vehiculos,id'],
            'tipo'             => ['required', 'in:preventivo,correctivo'],
            'descripcion'      => ['required', 'string', 'max:2000'],
            'odometro_ingreso' => ['required', 'integer', 'min:0'],
            'fecha_programada' => ['required', 'date'],
            'notas'            => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $v) {
                $vehiculo = Vehiculo::find($this->input('vehiculo_id'));
                if (! $vehiculo) return;

                // RN-06: no crear órdenes para vehículos dados de baja
                if ($vehiculo->estatus === Vehiculo::ESTATUS_BAJA) {
                    $v->errors()->add('vehiculo_id', 'No se pueden crear órdenes para vehículos dados de baja.');
                }

                // RN-02: una sola orden activa por vehículo
                if ($vehiculo->tieneOrdenActiva()) {
                    $v->errors()->add('vehiculo_id', 'El vehículo ya tiene una orden abierta o en proceso.');
                }

                // RN-04: odómetro no puede ser menor al actual
                if ((int) $this->input('odometro_ingreso') < $vehiculo->odometro_actual) {
                    $v->errors()->add(
                        'odometro_ingreso',
                        "El odómetro no puede ser menor al actual del vehículo ({$vehiculo->odometro_actual} km)."
                    );
                }
            },
        ];
    }
}