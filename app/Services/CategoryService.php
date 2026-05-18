<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CategoryService
{
    /**
     * Tiempo de expiración del caché en segundos (10 minutos).
     */
    private const CACHE_TTL = 600;

    /**
     * Tag de caché utilizado para invalidar todas las entradas relacionadas a categorías.
     */
    private const CACHE_TAG = 'categories';

    /**
     * Retorna las categorías paginadas con cursor para mayor eficiencia en grandes volúmenes.
     * Soporta filtros opcionales:
     *   - name:   búsqueda parcial LIKE (aprovecha el índice de prefijo en columna name)
     *   - status: filtro exacto booleano
     */
    public function getAllPaginated(int $perPage = 15, array $filters = []): CursorPaginator
    {
        $query = Category::orderByDesc('created_at');

        // Filtro por nombre (búsqueda parcial, case-insensitive)
        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        // Filtro por estado activo/inactivo
        if (isset($filters['status'])) {
            $query->where('status', (bool) $filters['status']);
        }

        return $query->cursorPaginate($perPage);
    }

    /**
     * Busca una categoría por su ID.
     * Usa caché individual por ID para evitar queries repetidas.
     */
    public function findOrFail(int $id): Category
    {
        return Cache::tags([self::CACHE_TAG])->remember(
            "categories.{$id}",
            self::CACHE_TTL,
            fn () => Category::findOrFail($id)
        );
    }

    /**
     * Crea una nueva categoría e invalida el caché del listado.
     */
    public function create(array $data): Category
    {
        $category = Category::create($data);

        Log::info('Categoría creada', ['category_id' => $category->id]);

        // Invalida todo el caché de categorías para reflejar el nuevo registro
        $this->flushCache();

        return $category;
    }

    /**
     * Actualiza una categoría existente e invalida el caché relacionado.
     */
    public function update(Category $category, array $data): Category
    {
        $category->fill($data)->save();

        Log::info('Categoría actualizada', ['category_id' => $category->id]);

        $this->flushCache();

        return $category;
    }

    /**
     * Elimina una categoría e invalida el caché relacionado.
     */
    public function delete(Category $category): void
    {
        $categoryId = $category->id;

        $category->delete();

        Log::info('Categoría eliminada', ['category_id' => $categoryId]);

        $this->flushCache();
    }

    /**
     * Invalida todas las entradas de caché etiquetadas con el tag de categorías.
     */
    private function flushCache(): void
    {
        Cache::tags([self::CACHE_TAG])->flush();
    }
}
