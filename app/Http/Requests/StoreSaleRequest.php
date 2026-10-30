<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->club_id !== null;
    }

    public function rules(): array
    {
        return [
            'request_key' => ['required', 'uuid'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer', 'pos', 'booking'])],
            'booking_id' => ['nullable', 'required_if:payment_method,booking', 'prohibited_unless:payment_method,booking', 'integer', Rule::exists('bookings', 'id')->where('club_id', $this->user()->club_id)],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'distinct', Rule::exists('products', 'id')->where('club_id', $this->user()->club_id)],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10000'],
        ];
    }
}
