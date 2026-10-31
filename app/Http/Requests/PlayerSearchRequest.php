<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlayerSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city' => ['nullable', 'string', 'max:100'],
            'sport' => ['nullable', Rule::in(['padel', 'futbolito', 'tenis'])],
            'date' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.today()->addDays(90)->toDateString()],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
