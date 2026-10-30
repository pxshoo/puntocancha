<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use BelongsToClub;

    protected $fillable = ['user_id', 'plan_id', 'status', 'billing_cycle', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isCurrent(): bool
    {
        return in_array($this->status, ['trialing', 'active'], true)
            && $this->starts_at->lte(now())
            && ($this->ends_at === null || $this->ends_at->gt(now()));
    }
}
