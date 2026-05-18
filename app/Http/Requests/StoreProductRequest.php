<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Todos los usuarios autenticados pueden crear productos.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para la creación de un producto.
     */
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price'       => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'stock'       => ['sometimes', 'integer', 'min:0'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status'      => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     */
    public function messages(): array
    {
        return [
            'name.required'      => 'El nombre del producto es obligatorio.',
            'name.max'           => 'El nombre no puede superar los 255 caracteres.',
            'price.required'     => 'El precio del producto es obligatorio.',
            'price.numeric'      => 'El precio debe ser un valor numérico.',
            'price.min'          => 'El precio no puede ser negativo.',
            'stock.integer'      => 'El stock debe ser un número entero.',
            'stock.min'          => 'El stock no puede ser negativo.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
        ];
    }
}
