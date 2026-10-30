<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->club_id !== null;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'not_in:0', 'min:-100000000', 'max:100000000'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
