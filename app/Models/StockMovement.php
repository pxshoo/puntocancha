<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use BelongsToClub;

    protected $fillable = ['product_id', 'user_id', 'sale_id', 'quantity', 'stock_after', 'reason'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
