<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UsageTrackingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subscription_id' => ['sometimes', 'nullable', 'uuid', 'exists:subscriptions,id'],
            'billing_cycle_date' => ['sometimes', 'date'],
            'messages_used' => ['sometimes', 'integer', 'min:0'],
            'documents_generated' => ['sometimes', 'integer', 'min:0'],
            'cases_created' => ['sometimes', 'integer', 'min:0'],
            'ai_cost_accumulated' => ['sometimes', 'numeric', 'min:0', 'max:999999.9999'],
            'input_tokens_used' => ['sometimes', 'integer', 'min:0'],
            'output_tokens_used' => ['sometimes', 'integer', 'min:0'],
            'cost_threshold_reached' => ['sometimes', 'boolean'],
            'threshold_reached_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
