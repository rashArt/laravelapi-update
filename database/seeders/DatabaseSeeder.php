<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    private const CATEGORY_COUNT = 100;
    private const PRODUCT_COUNT = 10000;
    private const STOCK_MOVEMENT_COUNT = 30000;
    private const CHUNK_SIZE = 1000;

    public function run()
    {
        // Crear usuario admin
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@legacy.test'],
            [
                'name' => 'Admin Legacy',
                'password' => Hash::make('password'),
                'api_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Optimización: Generar todas las categorías en array y hacer insert en chunks
        $this->seedCategories();

        // Optimización: Generar todos los productos en array y hacer insert en chunks
        $this->seedProducts();

        // Optimización: Generar todos los movimientos en chunks para evitar memory issues
        $this->seedStockMovements();
    }

    /**
     * Siembra categorías en chunks de 100
     */
    private function seedCategories(): void
    {
        $categories = [];

        for ($i = 1; $i <= self::CATEGORY_COUNT; $i++) {
            $categories[] = [
                'name' => 'Categoria ' . $i,
                'description' => 'Descripción de categoría ' . $i,
                'status' => $i % 7 !== 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insertar en chunks de 100 (las categorías son pocas, un chunk es suficiente)
        $this->insertInChunks('categories', $categories, 100);
    }

    /**
     * Siembra productos en chunks de 1000
     * OPTIMIZACIÓN CRÍTICA: Cambiado de 10000 inserts individuales a 10 inserts de 1000 registros
     */
    private function seedProducts(): void
    {
        $products = [];

        for ($i = 1; $i <= self::PRODUCT_COUNT; $i++) {
            $products[] = [
                'name' => 'Producto Legacy ' . $i,
                'description' => 'Producto generado para prueba de rendimiento ' . $i,
                'price' => rand(1000, 30000) / 100,
                'stock' => rand(0, 200),
                'category_id' => rand(1, self::CATEGORY_COUNT),
                'status' => $i % 9 !== 0,
                'created_at' => now()->subDays(rand(0, 365)),
                'updated_at' => now(),
            ];
        }

        // Insertar en chunks de 1000 (10 inserts en lugar de 10000)
        $this->insertInChunks('products', $products, self::CHUNK_SIZE);
    }

    /**
     * Siembra movimientos de stock en chunks de 1000
     * OPTIMIZACIÓN CRÍTICA: Cambiado de 30000 inserts individuales a 30 inserts de 1000 registros
     */
    private function seedStockMovements(): void
    {
        $movements = [];

        for ($i = 1; $i <= self::STOCK_MOVEMENT_COUNT; $i++) {
            $movements[] = [
                'product_id' => rand(1, self::PRODUCT_COUNT),
                'type' => rand(0, 1) ? 'entrada' : 'salida',
                'quantity' => rand(1, 20),
                'reason' => 'Movimiento legacy ' . $i,
                'user_id' => 1,
                'created_at' => now()->subDays(rand(0, 180)),
                'updated_at' => now(),
            ];
        }

        // Insertar en chunks de 1000 (30 inserts en lugar de 30000)
        $this->insertInChunks('stock_movements', $movements, self::CHUNK_SIZE);
    }

    /**
     * Inserta datos en chunks para optimizar consumo de memoria y conexión
     *
     * @param string $table Tabla destino
     * @param array $data Array de registros a insertar
     * @param int $chunkSize Tamaño de cada chunk
     */
    private function insertInChunks(string $table, array $data, int $chunkSize = 1000): void
    {
        $chunks = array_chunk($data, $chunkSize);

        foreach ($chunks as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
}
