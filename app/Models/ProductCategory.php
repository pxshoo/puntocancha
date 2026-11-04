<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use BelongsToClub;

    public const DEFAULT_NAMES = ['Bebidas', 'Alimentos', 'Dulces', 'Snacks', 'Accesorios', 'Arriendos', 'Otros'];

    protected $fillable = ['name'];
}
