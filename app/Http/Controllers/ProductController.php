<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\StoreStockMovementRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementCollection;
use App\Http\Resources\StockMovementResource;
use App\Http\Traits\ApiResponse;
use App\Models\Product;
use App\Services\ProductService;
use App\Services\StockMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProductService $productService,
        private readonly StockMovementService $stockMovementService,
    ) {}

    /**
     * Lista los productos paginados con cursor.
     * Acepta query params: ?per_page=N, ?name=, ?category_id=, ?status=, ?min_price=, ?max_price=
     */
    public function index(Request $request): JsonResponse
    {
        // Validación de parámetros de consulta antes de llegar al servicio
        $validated = validator($request->query(), [
            'per_page'    => ['sometimes', 'integer', 'min:1', 'max:100'],
            'name'        => ['sometimes', 'string', 'max:255'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'status'      => ['sometimes', 'boolean'],
            'min_price'   => ['sometimes', 'numeric', 'min:0'],
            'max_price'   => ['sometimes', 'numeric', 'min:0'],
        ])->validate();

        $perPage = (int) ($validated['per_page'] ?? 15);
        $filters = array_filter(
            array_intersect_key($validated, array_flip(['name', 'category_id', 'status', 'min_price', 'max_price'])),
            fn ($v) => !is_null($v)
        );

        $products = $this->productService->getAllPaginated($perPage, $filters);

        return $this->paginatedResponse(new ProductCollection($products));
    }

    /**
     * Crea un nuevo producto con validación robusta via FormRequest.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return $this->successResponse(new ProductResource($product), 201);
    }

    /**
     * Retorna el detalle de un producto con su categoría.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $product = $this->productService->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Producto no encontrado.', 404);
        }

        return $this->successResponse(new ProductResource($product));
    }

    /**
     * Actualiza un producto existente e invalida el caché relacionado.
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            $product = $this->productService->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Producto no encontrado.', 404);
        }

        $product = $this->productService->update($product, $request->validated());

        return $this->successResponse(new ProductResource($product));
    }

    /**
     * Elimina un producto e invalida el caché relacionado.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $product = $this->productService->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Producto no encontrado.', 404);
        }

        $this->productService->delete($product);

        return $this->successResponse(null, 204);
    }

    /**
     * Lista los movimientos de stock de un producto con paginación cursor.
     * Acepta query params: ?per_page=N, ?type=, ?user_id=, ?date_from=, ?date_to=
     */
    public function stockMovements(Request $request, int $id): JsonResponse
    {
        try {
            $product = $this->productService->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Producto no encontrado.', 404);
        }

        // Validación de parámetros de consulta
        $validated = validator($request->query(), [
            'per_page'  => ['sometimes', 'integer', 'min:1', 'max:100'],
            'type'      => ['sometimes', 'string', 'in:entrada,salida'],
            'user_id'   => ['sometimes', 'integer', 'exists:users,id'],
            'date_from' => ['sometimes', 'date_format:Y-m-d'],
            'date_to'   => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ])->validate();

        $perPage = (int) ($validated['per_page'] ?? 15);
        $filters = array_filter(
            array_intersect_key($validated, array_flip(['type', 'user_id', 'date_from', 'date_to'])),
            fn ($v) => !is_null($v)
        );

        $movements = $this->stockMovementService->getAllByProductPaginated($product, $perPage, $filters);

        return $this->paginatedResponse(new StockMovementCollection($movements));
    }

    /**
     * Registra un movimiento de stock para un producto.
     * El usuario se resuelve desde la sesión autenticada (no viene en el payload).
     */
    public function storeStockMovement(StoreStockMovementRequest $request, int $id): JsonResponse
    {
        try {
            $product = $this->productService->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Producto no encontrado.', 404);
        }

        $data = $request->validated();

        try {
            $movement = $this->stockMovementService->registerMovement(
                product: $product,
                type: StockMovementType::from($data['type']),
                quantity: $data['quantity'],
                userId: $request->user()->id,
                reason: $data['reason'] ?? null,
            );
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse(new StockMovementResource($movement), 201);
    }
}
