<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'club_admin';
    }

    public function rules(): array
    {
        return [
            'city' => ['nullable', 'required_if:marketplace_enabled,1', 'string', 'max:100'],
            'address' => ['nullable', 'required_if:marketplace_enabled,1', 'string', 'max:255'],
            'phone' => ['nullable', 'required_if:marketplace_enabled,1', 'string', 'regex:/^\+?[0-9 ()-]{8,25}$/'],
            'marketplace_enabled' => ['sometimes', 'boolean'],
            'opens_at' => ['required', 'date_format:H:i'],
            'closes_at' => ['required', 'regex:/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$|^24:00$/', 'after:opens_at'],
            'slot_minutes' => ['required', 'integer', Rule::in([60, 90])],
            'seed_products' => ['sometimes', 'boolean'],
        ];
    }
}
