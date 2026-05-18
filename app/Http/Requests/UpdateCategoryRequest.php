<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Todos los usuarios autenticados pueden actualizar categorías.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para la actualización de una categoría.
     * Se ignora el registro actual en la validación de unicidad.
     */
    public function rules(): array
    {
        $categoryId = $this->route('id');

        return [
            'name'        => ['sometimes', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($categoryId)],
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
            'name.unique' => 'Ya existe una categoría con ese nombre.',
            'name.max'    => 'El nombre no puede superar los 255 caracteres.',
        ];
    }
}
