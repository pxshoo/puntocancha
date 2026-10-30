<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Club extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    protected static function booted(): void
    {
        static::creating(function (Club $club) {
            $club->slug = (Str::slug($club->name) ?: 'club').'-'.Str::lower(Str::random(8));
        });
    }

    protected function casts(): array
    {
        return ['onboarding_completed_at' => 'datetime', 'slot_minutes' => 'integer'];
    }
}
