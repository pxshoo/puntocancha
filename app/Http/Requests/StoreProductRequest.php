<?php

namespace App\Http\Requests;

use App\Models\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->club_id !== null;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('category_choice')) {
            return;
        }
        $choice = $this->input('category_choice');
        if (! is_string($choice)) {
            return;
        }
        $this->merge([
            'category_id' => str_starts_with($choice, 'existing:') ? substr($choice, 9) : null,
            'default_category' => str_starts_with($choice, 'default:') ? substr($choice, 8) : null,
            'new_category' => $choice === 'new' ? $this->input('new_category') : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'category_choice' => ['nullable', 'string', 'regex:/^(new|existing:[0-9]+|default:.+)$/'],
            'default_category' => ['nullable', Rule::in(ProductCategory::DEFAULT_NAMES)],
            'new_category' => ['required_if:category_choice,new', 'nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', Rule::exists('product_categories', 'id')->where('club_id', $this->user()->club_id)],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->where('club_id', $this->user()->club_id)->ignore($this->route('product')?->id)],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'sale_price' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'alert_min_stock' => ['required', 'integer', 'min:0', 'max:100000000'],
            'initial_stock' => [$this->isMethod('POST') ? 'required' : 'prohibited', 'integer', 'min:0', 'max:100000000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
