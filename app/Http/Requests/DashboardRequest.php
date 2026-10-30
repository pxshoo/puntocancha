<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->club_id !== null;
    }

    public function rules(): array
    {
        return [
            'period' => ['sometimes', Rule::in(['today', 'week', 'month', 'custom'])],
            'from' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d'],
            'to' => ['required_if:period,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from', 'before_or_equal:'.now()->addYear()->toDateString()],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if (! $validator->errors()->any() && $this->input('period') === 'custom') {
                $days = CarbonImmutable::parse($this->input('from'))->diffInDays(CarbonImmutable::parse($this->input('to')));
                if ($days > 365) {
                    $validator->errors()->add('to', 'Selecciona un rango de hasta 366 días.');
                }
            }
        });
    }
}
