<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryResource;
use App\Http\Traits\ApiResponse;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    /**
     * Lista las categorías paginadas con cursor.
     * Acepta query param ?per_page=N (máximo 100).
     */
    public function index(Request $request): JsonResponse
    {
        // Validación de parámetros de consulta antes de llegar al servicio
        $validated = validator($request->query(), [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'name'     => ['sometimes', 'string', 'max:255'],
            'status'   => ['sometimes', 'boolean'],
        ])->validate();

        $perPage = (int) ($validated['per_page'] ?? 15);
        $filters = array_filter(
            ['name' => $validated['name'] ?? null, 'status' => $validated['status'] ?? null],
            fn ($v) => !is_null($v)
        );

        $categories = $this->categoryService->getAllPaginated($perPage, $filters);

        return $this->paginatedResponse(new CategoryCollection($categories));
    }

    /**
     * Crea una nueva categoría con validación robusta via FormRequest.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->validated());

        return $this->successResponse(new CategoryResource($category), 201);
    }

    /**
     * Retorna el detalle de una categoría. Respuesta desde caché Redis.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Categoría no encontrada.', 404);
        }

        return $this->successResponse(new CategoryResource($category));
    }

    /**
     * Actualiza una categoría existente e invalida el caché relacionado.
     */
    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Categoría no encontrada.', 404);
        }

        $category = $this->categoryService->update($category, $request->validated());

        return $this->successResponse(new CategoryResource($category));
    }

    /**
     * Elimina una categoría e invalida el caché relacionado.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Categoría no encontrada.', 404);
        }

        $this->categoryService->delete($category);

        return $this->successResponse(null, 204);
    }
}
