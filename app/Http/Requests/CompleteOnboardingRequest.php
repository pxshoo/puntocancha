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
            'opens_at' => ['required', 'date_format:H:i'],
            'closes_at' => ['required', 'date_format:H:i', 'after:opens_at'],
            'slot_minutes' => ['required', 'integer', Rule::in([60, 90])],
            'seed_products' => ['sometimes', 'boolean'],
        ];
    }
}
