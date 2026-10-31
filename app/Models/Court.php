<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Court extends Model
{
    use BelongsToClub, \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = ['minimum_deposit', 'address', 'city', 'name', 'default_hourly_rate', 'is_active', 'sport_type', 'surface_type', 'is_covered', 'setup_key'];

    protected function casts(): array
    {
        return ['minimum_deposit' => 'integer', 'is_active' => 'boolean', 'default_hourly_rate' => 'decimal:2'];
    }
}
