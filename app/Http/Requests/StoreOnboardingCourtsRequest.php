<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOnboardingCourtsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'club_admin';
    }

    public function rules(): array
    {
        return [
            'courts' => ['required', 'array', 'min:1'],
            'courts.*.key' => ['required', 'uuid', 'distinct'],
            'courts.*.name' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
            'courts.*.sport_type' => ['required', Rule::in(['padel', 'futbolito', 'tenis'])],
            'courts.*.surface_type' => ['required', 'string', 'max:100'],
            'courts.*.is_covered' => ['required', 'boolean'],
            'courts.*.minimum_deposit' => ['sometimes', 'required', 'integer', 'min:0', 'max:99999999'],
            'courts.*.default_hourly_rate' => ['required', 'integer', 'min:1', 'max:99999999'],
        ];
    }
}
