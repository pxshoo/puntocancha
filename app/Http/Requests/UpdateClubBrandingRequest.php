<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClubBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'club_admin' && $this->user()->club_id;
    }

    public function rules(): array
    {
        return [
            'brand_palette' => ['required', Rule::in(array_keys(config('branding.palettes')))],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=64,min_height=64,max_width=2000,max_height=2000'],
            'remove_logo' => ['sometimes', 'boolean', Rule::prohibitedIf($this->hasFile('logo'))],
        ];
    }

    public function attributes(): array
    {
        return ['brand_palette' => 'paleta de colores', 'logo' => 'logo', 'remove_logo' => 'quitar logo'];
    }
}
