<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true), 'cost_price' => '500.00', 'sale_price' => '1000.00',
            'stock' => 10, 'alert_min_stock' => 2, 'is_active' => true,
        ];
    }
}
