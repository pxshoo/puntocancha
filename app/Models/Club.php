<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Club extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    protected $hidden = ['webpay_api_key', 'webpay_commerce_code'];

    protected static function booted(): void
    {
        static::creating(function (Club $club) {
            if (app()->environment('local')) {
                $club->webpay_mode = 'integration';
            }
            $club->slug = (Str::slug($club->name) ?: 'club').'-'.Str::lower(Str::random(8));
        });
    }

    protected function casts(): array
    {
        return ['hourly_rates' => 'array', 'latitude' => 'float', 'longitude' => 'float', 'minimum_deposit' => 'integer', 'webpay_api_key' => 'encrypted', 'onboarding_completed_at' => 'datetime', 'slot_minutes' => 'integer', 'marketplace_enabled' => 'boolean'];
    }

    public function mapUrl(?Court $court = null): string
    {
        $address = $court?->address ?: $this->address;
        $city = $court?->city ?: $this->city;
        $query = collect([$address, $city])->filter()->implode(', ');
        if ($this->latitude !== null && $this->longitude !== null && $address === $this->address && $city === $this->city) {
            $query = $this->latitude.','.$this->longitude;
        }

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($query);
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return route('club.logo', ['club' => $this->slug, 'version' => pathinfo($this->logo_path, PATHINFO_FILENAME)]);
    }

    public function courts(): HasMany
    {
        return $this->hasMany(Court::class);
    }

    public function whatsappUrl(string $message): ?string
    {
        $phone = preg_replace('/\D/', '', $this->phone ?? '');
        if (strlen($phone) === 9) {
            $phone = '56'.$phone;
        }

        return strlen($phone) >= 10 ? 'https://wa.me/'.$phone.'?text='.rawurlencode($message) : null;
    }
}
