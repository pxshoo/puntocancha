<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuperAdminActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_superadmin;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:approve,reject,grant,suspend,reset'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'confirmed_bank' => ['required_if:action,approve', 'accepted_if:action,approve'],
            'amount' => ['required_if:action,approve', 'nullable', 'integer', 'min:1'],
            'authorization' => ['required_if:action,approve', 'nullable', 'regex:/^[a-zA-Z0-9-]{1,50}$/'],
        ];
    }
}
