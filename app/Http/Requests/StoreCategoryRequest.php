<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Todos los usuarios autenticados pueden crear categorías.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para la creación de una categoría.
     */
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status'      => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.unique'   => 'Ya existe una categoría con ese nombre.',
            'name.max'      => 'El nombre no puede superar los 255 caracteres.',
        ];
    }
}
