<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['*'];

    protected $hidden = ['token', 'token_hash', 'request_hash', 'session_id'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'token' => 'encrypted', 'terms_snapshot' => 'array', 'privacy_snapshot' => 'array', 'accepted_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }
}
