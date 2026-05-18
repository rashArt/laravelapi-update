<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductService
{
    /**
     * Tiempo de expiración del caché en segundos (10 minutos).
     */
    private const CACHE_TTL = 600;


    /**
     * Retorna los productos paginados con cursor junto a su categoría.
     * Soporta filtros opcionales:
     *   - name:        búsqueda parcial LIKE sobre el nombre del producto
     *   - category_id: filtro exacto por categoría
     *   - status:      filtro exacto booleano
     *   - min_price:   precio mínimo
     *   - max_price:   precio máximo
     */
    public function getAllPaginated(int $perPage = 15, array $filters = []): CursorPaginator
    {
        $query = Product::with('category')->orderByDesc('created_at');

        // Filtro por nombre (búsqueda parcial, case-insensitive)
        if (!empty($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        // Filtro por categoría
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filtro por estado activo/inactivo
        if (isset($filters['status'])) {
            $query->where('status', (bool) $filters['status']);
        }

        // Filtro por rango de precio mínimo
        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        // Filtro por rango de precio máximo
        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        return $query->cursorPaginate($perPage);
    }

    /**
     * Busca un producto por su ID con su categoría.
     * Usa caché individual por ID para evitar queries repetidas.
     */
    public function findOrFail(int $id): Product
    {
        return Cache::remember(
            "products.{$id}",
            self::CACHE_TTL,
            fn () => Product::with('category')->findOrFail($id)
        );
    }

    /**
     * Crea un nuevo producto e invalida el caché del listado.
     */
    public function create(array $data): Product
    {
        $product = Product::create($data);

        Log::info('Producto creado', ['product_id' => $product->id]);

        // Invalida contadores del dashboard al crear un producto nuevo
        $this->flushCache($product->id);

        // Carga la relación de categoría para la respuesta
        return $product->load('category');
    }

    /**
     * Actualiza un producto existente e invalida el caché relacionado.
     */
    public function update(Product $product, array $data): Product
    {
        $product->fill($data)->save();

        Log::info('Producto actualizado', ['product_id' => $product->id]);

        $this->flushCache($product->id);

        return $product->load('category');
    }

    /**
     * Elimina un producto e invalida el caché relacionado.
     */
    public function delete(Product $product): void
    {
        $productId = $product->id;

        $product->delete();

        Log::info('Producto eliminado', ['product_id' => $productId]);

        $this->flushCache($productId);
    }

    /**
     * Invalida la clave de caché del registro afectado y los contadores del dashboard.
     */
    private function flushCache(int $id): void
    {
        Cache::forget("products.{$id}");
        Cache::forget('products_count');
    }
}
