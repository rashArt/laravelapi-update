<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StockMovementCollection extends ResourceCollection
{
    public $collects = StockMovementResource::class;

    /**
     * Transforma la colección de movimientos de stock a representación JSON.
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
