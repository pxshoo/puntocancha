<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Court extends Model
{
    use BelongsToClub, \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = ['name', 'default_hourly_rate', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'default_hourly_rate' => 'decimal:2'];
    }
}
