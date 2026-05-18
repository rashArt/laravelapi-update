<?php

namespace App\Http\Resources;

use App\Enums\StockMovementType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    /**
     * Transforma el modelo StockMovement a representación JSON para la API.
     * Incluye el usuario asociado si fue cargado con eager loading.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'product_id' => $this->product_id,
            'type'       => $this->type instanceof StockMovementType ? $this->type->value : $this->type,
            'quantity'   => $this->quantity,
            'reason'     => $this->reason,
            'user'       => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
