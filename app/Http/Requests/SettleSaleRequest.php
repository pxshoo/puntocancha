<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettleSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->club_id !== null;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(['cash', 'transfer', 'pos'])],
        ];
    }
}
