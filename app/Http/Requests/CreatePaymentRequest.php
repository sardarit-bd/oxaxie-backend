<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentRequest extends FormRequest
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
            'amount' => 'required|numeric',
            'currency' => 'nullable|string|size:3',
            'gateway' => 'nullable|string|in:stripe',
            'description' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Payment amount is required',
            'amount.min' => 'Payment amount must be at least 0.50',
            'gateway.in' => 'Invalid payment gateway selected',
        ];
    }
}
