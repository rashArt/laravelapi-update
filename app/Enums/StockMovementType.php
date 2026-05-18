<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Entrada = 'entrada';
    case Salida  = 'salida';
}
