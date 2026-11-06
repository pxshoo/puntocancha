<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartSubscriptionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'club_admin' && $this->user()->club_id;
    }

    public function rules(): array
    {
        return [
            'link_test' => ['sometimes', 'boolean'],
            'accept_terms' => ['required', 'accepted'],
            'accept_privacy' => ['required', 'accepted'],
            'policy_version' => ['required', 'in:'.config('billing.version')],
            'quoted_amount' => ['required', 'integer', 'min:1'],
            'request_key' => ['required', 'uuid'],
        ];
    }

    public function attributes(): array
    {
        return ['accept_terms' => 'aceptación de condiciones de uso', 'accept_privacy' => 'aceptación de la política de privacidad', 'policy_version' => 'versión de las políticas'];
    }
}
