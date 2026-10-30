<?php

namespace Database\Factories;

use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'court_id' => Court::factory(), 'customer_name' => fake()->name(),
            'customer_phone' => '912345678', 'start_time' => today()->setHour(15),
            'end_time' => today()->setHour(16), 'total_price' => '20000.00',
            'deposit_paid' => 0, 'status' => 'confirmed', 'payment_method' => 'cash',
        ];
    }
}
