<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = ['slug', 'name', 'price', 'interval', 'court_limit'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'court_limit' => 'integer'];
    }
}
