<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClubSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'club_admin';
    }

    public function rules(): array
    {
        return [
            'minimum_deposit' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:99999999'],
            'webpay_mode' => ['sometimes', Rule::in(['disabled', 'integration'])],
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ()-]{8,25}$/'],
            'image_url' => ['nullable', 'url', 'starts_with:https://', 'max:2048'],
            'opens_at' => ['required', 'date_format:H:i'],
            'closes_at' => ['required', 'date_format:H:i', 'after:opens_at'],
            'slot_minutes' => ['required', 'integer', Rule::in([60, 90])],
            'marketplace_enabled' => ['required', 'boolean'],
            'booking_terms' => ['required', 'string', 'max:2000'],
            'cancellation_policy' => ['required', 'string', 'max:2000'],
        ];
    }
}
