<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'club_admin';
    }

    public function rules(): array
    {
        return [
            'minimum_deposit' => ['sometimes', 'required', 'integer', 'min:0', 'max:99999999'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'sport_type' => ['required', Rule::in(['padel', 'futbolito', 'tenis'])],
            'surface_type' => ['required', 'string', 'max:100'],
            'is_covered' => ['required', 'boolean'],
            'default_hourly_rate' => ['required', 'integer', 'min:1', 'max:99999999'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
