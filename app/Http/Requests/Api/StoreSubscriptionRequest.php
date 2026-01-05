<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_tier' => ['required', Rule::in(['free', 'pro', 'pro_plus'])],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'current_period_start' => ['nullable', 'date'],
            'current_period_end' => ['nullable', 'date', 'after:current_period_start'],
            'stripe_subscription_id' => ['nullable', 'string', 'max:255', 'unique:subscriptions,stripe_subscription_id'],
            'stripe_customer_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_tier.required' => 'Please select a subscription plan.',
            'plan_tier.in' => 'Invalid subscription plan selected.',
            'monthly_price.required' => 'Monthly price is required.',
            'monthly_price.numeric' => 'Monthly price must be a valid number.',
            'current_period_end.after' => 'Period end date must be after the start date.',
        ];
    }
}