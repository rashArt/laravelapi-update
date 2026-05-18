<?php

namespace App\Http\Requests;

use App\Enums\StockMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreStockMovementRequest extends FormRequest
{
    /**
     * Todos los usuarios autenticados pueden registrar movimientos de stock.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el registro de un movimiento de stock.
     * El usuario se resuelve desde la sesión autenticada (no viene en el payload).
     */
    public function rules(): array
    {
        return [
            'type'     => ['required', new Enum(StockMovementType::class)],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason'   => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     */
    public function messages(): array
    {
        return [
            'type.required'     => 'El tipo de movimiento es obligatorio.',
            'type.Illuminate\Validation\Rules\Enum' => 'El tipo debe ser "entrada" o "salida".',
            'quantity.required' => 'La cantidad es obligatoria.',
            'quantity.integer'  => 'La cantidad debe ser un número entero.',
            'quantity.min'      => 'La cantidad debe ser al menos 1.',
            'reason.max'        => 'El motivo no puede superar los 1000 caracteres.',
        ];
    }
}
