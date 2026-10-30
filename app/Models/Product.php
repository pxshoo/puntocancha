<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use BelongsToClub, HasFactory;

    protected $fillable = ['name', 'category_id', 'barcode', 'cost_price', 'sale_price', 'alert_min_stock', 'is_active'];

    protected function casts(): array
    {
        return ['cost_price' => 'decimal:2', 'sale_price' => 'decimal:2', 'stock' => 'integer', 'is_active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }
}
