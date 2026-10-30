<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use BelongsToClub;

    protected $fillable = ['booking_id', 'user_id', 'request_key', 'total_amount', 'cost_amount', 'status', 'payment_method', 'paid_at'];

    protected function casts(): array
    {
        return ['total_amount' => 'decimal:2', 'cost_amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
