<?php

namespace App\Http\Requests\Orden;

use App\Models\Orden;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrdenStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Orden|null $orden */
        $orden      = $this->route('orden');
        $permitidos = Orden::TRANSICIONES[$orden?->status] ?? [];   // ← status

        return [
            'status'       => ['required', 'string', Rule::in($permitidos)],
            'costo'        => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'fecha_cierre' => ['nullable', 'date'],
            'notas'        => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Ese cambio de estatus no está permitido desde el estado actual.',
        ];
    }
}