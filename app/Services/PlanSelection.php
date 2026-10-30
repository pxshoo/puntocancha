<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PlanSelection
{
    public function capture(Request $request): ?array
    {
        if ($request->query->has('plan') || $request->query->has('plan_id') || $request->query->has('billing')) {
            $data = Validator::make([
                'plan' => $request->query('plan', $request->query('plan_id')),
                'billing' => $request->query('billing', 'monthly'),
            ], [
                'plan' => ['required', 'string', Rule::in(array_merge(array_keys(config('plans')), ['1', '2', '3']))],
                'billing' => ['required', 'string', Rule::in(['monthly', 'annual'])],
            ])->validate();
            $key = ['1' => 'basic', '2' => 'pro', '3' => 'premium'][(string) $data['plan']] ?? $data['plan'];
            $request->session()->put('selected_plan', ['key' => $key, 'billing' => $data['billing']]);
        }
        $selected = $request->session()->get('selected_plan');
        if (! $selected || ! array_key_exists($selected['key'] ?? '', config('plans'))) {
            return null;
        }

        return config('plans.'.$selected['key']) + $selected + ['id' => Plan::where('slug', $selected['key'])->value('id')];
    }
}
