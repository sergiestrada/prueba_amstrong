<?php

namespace App\Http\Requests\Orden;

use App\Models\Orden;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrdenStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización real se valida en OrdenController::updateStatus
    }

    public function rules(): array
    {
        /** @var Orden|null $orden */
        $orden      = $this->route('orden');
        $current    = $orden?->status;
        $permitidos = Orden::TRANSICIONES[$current] ?? [];

        return [
            'status'     => ['required', 'string', Rule::in($permitidos)],
            'notas'      => ['nullable', 'string', 'max:2000'],
            'costo_real' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Ese cambio de estatus no está permitido desde el estado actual.',
        ];
    }
}