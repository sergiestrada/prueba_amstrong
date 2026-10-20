<?php

namespace App\Http\Requests\Orden;

use App\Models\Orden;
use App\Models\Vehiculo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'tipo'             => ['sometimes', 'in:preventivo,correctivo'],
            'descripcion'      => ['sometimes', 'string', 'max:2000'],
            'odometro_ingreso' => ['sometimes', 'integer', 'min:0'],
            'fecha_programada' => ['sometimes', 'date'],
            'notas'            => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $v) {
                $orden = $this->route('orden');
                if (! $orden) return;

                if ($this->has('odometro_ingreso')) {
                    $vehiculo = Vehiculo::find($orden->vehiculo_id);
                    if ($vehiculo && (int) $this->input('odometro_ingreso') < $vehiculo->odometro_actual) {
                        $v->errors()->add(
                            'odometro_ingreso',
                            "El odómetro no puede ser menor al actual del vehículo ({$vehiculo->odometro_actual} km)."
                        );
                    }
                }
            },
        ];
    }
}