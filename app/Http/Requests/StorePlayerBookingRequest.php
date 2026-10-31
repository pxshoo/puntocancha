<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlayerBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'player';
    }

    public function rules(): array
    {
        return [
            'club_slug' => ['required', 'string', 'max:255'],
            'court_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.today()->addDays(90)->toDateString()],
            'start_time' => ['required', 'date_format:H:i'],
            'request_key' => ['required', 'uuid'],
            'quoted_price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999.99'],
            'accept_terms' => ['accepted'],
        ];
    }
}
