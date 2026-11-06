<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateClubSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'club_admin';
    }

    public function rules(): array
    {
        return [
            'booking_payment_link' => ['nullable', 'required_if:webpay_mode,payment_link', 'url', 'max:2048', 'regex:~^https://(?:www\.)?webpay\.cl/form-pay/[0-9]+/?$~'],
            'hourly_rates' => ['sometimes', 'array', 'max:24'],
            'hourly_rates.*' => ['array:from,until,rate'],
            'hourly_rates.*.from' => ['required', 'date_format:H:i'],
            'hourly_rates.*.until' => ['required', 'regex:/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$|^24:00$/'],
            'hourly_rates.*.rate' => ['required', 'integer', 'min:1', 'max:99999999'],
            'minimum_deposit' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:99999999'],
            'webpay_mode' => ['sometimes', Rule::in(['disabled', 'integration', 'payment_link'])],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-85,85'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ()-]{8,25}$/'],
            'image_url' => ['nullable', 'url', 'starts_with:https://', 'max:2048'],
            'opens_at' => ['required', 'date_format:H:i'],
            'closes_at' => ['required', 'regex:/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$|^24:00$/', 'after:opens_at'],
            'slot_minutes' => ['required', 'integer', Rule::in([60, 90])],
            'marketplace_enabled' => ['required', 'boolean'],
            'booking_terms' => ['required', 'string', 'max:2000'],
            'cancellation_policy' => ['required', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $previousEnd = null;
            foreach (collect($this->input('hourly_rates', []))->sortBy('from') as $index => $band) {
                if ($band['until'] <= $band['from'] || ($previousEnd !== null && $band['from'] < $previousEnd)) {
                    $validator->errors()->add("hourly_rates.{$index}.from", 'Las franjas deben tener fin posterior al inicio y no pueden solaparse. Usa 24:00 para medianoche.');
                }
                $previousEnd = $band['until'];
            }
        }];
    }
}
