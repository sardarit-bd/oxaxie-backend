<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundPaymentRequest extends FormRequest
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
            'amount' => 'nullable|numeric|min:0.50',
            'gateway' => 'nullable|string|in:stripe',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Refund amount must be at least 0.50',
            'gateway.in' => 'Invalid payment gateway selected',
        ];
    }
}
