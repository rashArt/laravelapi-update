<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    /**
     * Retorna los movimientos de stock de un producto paginados con cursor.
     * Soporta filtros opcionales:
     *   - type:       filtro exacto por tipo (entrada | salida)
     *   - user_id:    filtro exacto por usuario que registró el movimiento
     *   - date_from:  movimientos desde esta fecha (Y-m-d)
     *   - date_to:    movimientos hasta esta fecha (Y-m-d)
     */
    public function getAllByProductPaginated(
        Product $product,
        int $perPage = 15,
        array $filters = []
    ): CursorPaginator {
        $query = StockMovement::with('user')
            ->where('product_id', $product->id)
            ->orderByDesc('created_at');

        // Filtro por tipo de movimiento
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filtro por usuario que registró el movimiento
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filtro por rango de fecha inicio
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        // Filtro por rango de fecha fin
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->cursorPaginate($perPage);
    }

    /**
     * Registra un movimiento de stock para un producto dentro de una transacción.
     * Utiliza SELECT FOR UPDATE (lock pesimista) para evitar condiciones de carrera.
     * Lanza ValidationException si el movimiento dejaría el stock en negativo.
     */
    public function registerMovement(
        Product $product,
        StockMovementType $type,
        int $quantity,
        int $userId,
        ?string $reason = null
    ): StockMovement {
        return DB::transaction(function () use ($product, $type, $quantity, $userId, $reason) {
            // Lock pesimista: bloquea la fila del producto hasta fin de transacción
            // para evitar duplicidades y race conditions en escrituras concurrentes
            /** @var Product $lockedProduct */
            $lockedProduct = Product::lockForUpdate()->findOrFail($product->id);

            // Validar stock suficiente antes de registrar una salida
            if ($type === StockMovementType::Salida && $lockedProduct->stock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => [
                        "Stock insuficiente. Disponible: {$lockedProduct->stock}, solicitado: {$quantity}.",
                    ],
                ]);
            }

            // Actualizar stock según tipo de movimiento
            $lockedProduct->stock = $type === StockMovementType::Entrada
                ? $lockedProduct->stock + $quantity
                : $lockedProduct->stock - $quantity;

            $lockedProduct->save();

            // Crear el registro del movimiento
            $movement = StockMovement::create([
                'product_id' => $lockedProduct->id,
                'type'       => $type->value,
                'quantity'   => $quantity,
                'reason'     => $reason,
                'user_id'    => $userId,
            ]);

            Log::info('Movimiento de stock registrado', [
                'movement_id' => $movement->id,
                'product_id'  => $lockedProduct->id,
                'type'        => $type->value,
                'quantity'    => $quantity,
                'stock_after' => $lockedProduct->stock,
                'user_id'     => $userId,
            ]);

            return $movement->load('user');
        });
    }
}
