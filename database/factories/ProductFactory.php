<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name'        => $this->faker->unique()->words(3, true),
            'description' => $this->faker->sentence(),
            'price'       => $this->faker->randomFloat(2, 1, 9999),
            'stock'       => $this->faker->numberBetween(0, 500),
            'status'      => $this->faker->boolean(80),
            'category_id' => Category::factory(),
        ];
    }
}
