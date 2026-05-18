<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index()
    {
        // Conteos cacheados sin tags (TTL: 1 hora); se invalidan desde los Services al mutar datos
        $products   = Cache::remember('products_count', 3600, fn () => Product::count());
        $categories = Cache::remember('categories_count', 3600, fn () => Category::count());

        return $this->successResponse([
            'products'       => $products,
            'categories'     => $categories,
            'low_stock'      => Product::where('stock', '<', 10)->limit(20)->get(),
            'last_movements' => StockMovement::latest()->take(20)->get(),
        ]);
    }

    public function health()
    {
        try {
            DB::select('SELECT 1');

            return $this->successResponse(['database' => 'connected']);
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
