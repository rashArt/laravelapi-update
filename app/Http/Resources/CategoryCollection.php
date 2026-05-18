<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CategoryCollection extends ResourceCollection
{
    /**
     * Clase de recurso individual que representa cada elemento de la colección.
     * toArray() no se sobreescribe para que Laravel gestione automáticamente
     * los bloques 'links' y 'meta' al trabajar con CursorPaginator.
     */
    public $collects = CategoryResource::class;
}
