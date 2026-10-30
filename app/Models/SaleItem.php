<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use BelongsToClub;

    protected $fillable = ['sale_id', 'product_id', 'product_name', 'quantity', 'unit_cost_price', 'unit_sale_price', 'subtotal'];
}
