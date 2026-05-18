<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id'    => User::factory(),
            'type'       => $this->faker->randomElement(StockMovementType::cases())->value,
            'quantity'   => $this->faker->numberBetween(1, 100),
            'reason'     => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Movimiento de tipo entrada.
     */
    public function entrada(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockMovementType::Entrada->value,
        ]);
    }

    /**
     * Movimiento de tipo salida.
     */
    public function salida(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockMovementType::Salida->value,
        ]);
    }
}
