<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmClubPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'club_admin' && $this->route('payment')?->club_id === $this->user()->club_id;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:approve,reject'],
            'amount' => ['required_if:action,approve', 'nullable', 'integer', 'min:1'],
            'authorization' => ['required_if:action,approve', 'nullable', 'regex:/^[a-zA-Z0-9-]{1,50}$/'],
            'confirmed_bank' => ['required_if:action,approve', 'accepted_if:action,approve'],
        ];
    }
}
