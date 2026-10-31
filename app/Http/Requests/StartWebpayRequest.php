<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartWebpayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'club_slug' => ['required', 'string', 'max:255'],
            'court_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.today()->addDays(90)->toDateString()],
            'start_time' => ['required', 'date_format:H:i'],
            'customer_email' => ['required', 'email', 'max:255'],
            'payment_choice' => ['required', 'in:full,deposit'],
            'quoted_price' => ['required', 'integer', 'min:1'],
            'request_key' => ['required', 'uuid'],
            'accept_terms' => ['accepted'],
        ];
    }
}
