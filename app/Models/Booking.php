<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use BelongsToClub, \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'court_id', 'user_id', 'customer_name', 'customer_phone', 'customer_email',
        'start_time', 'end_time', 'total_price', 'deposit_paid', 'status', 'payment_method', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'total_price' => 'decimal:2',
            'deposit_paid' => 'decimal:2',
        ];
    }

    public function payment(): HasOne
    {
        return $this->hasOne(BookingPayment::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }
}
