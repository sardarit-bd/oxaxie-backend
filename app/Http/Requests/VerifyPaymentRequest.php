<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPaymentRequest extends FormRequest
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
            // For online payments (Stripe, PayPal, Razorpay, etc.)
            'payment_intent_id' => 'nullable|string|max:255',
            
            // For manual payments (Cash, Bank Transfer, etc.)
            'receipt_number' => 'nullable|string|max:255',
            'verified_by' => 'nullable|string|max:255',
            'amount_received' => 'nullable|numeric|min:0',
            'bank_reference' => 'nullable|string|max:255',
            'transfer_date' => 'nullable|date',
            'amount_transferred' => 'nullable|numeric|min:0',
            
            // Common fields
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_intent_id.string' => 'Payment intent ID must be a valid string.',
            'amount_received.numeric' => 'Amount received must be a valid number.',
            'amount_received.min' => 'Amount received must be greater than or equal to 0.',
            'amount_transferred.numeric' => 'Amount transferred must be a valid number.',
            'amount_transferred.min' => 'Amount transferred must be greater than or equal to 0.',
            'transfer_date.date' => 'Transfer date must be a valid date.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
        ];
    }
}