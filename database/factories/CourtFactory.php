<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CourtFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->words(2, true), 'default_hourly_rate' => '20000.00', 'is_active' => true];
    }
}
