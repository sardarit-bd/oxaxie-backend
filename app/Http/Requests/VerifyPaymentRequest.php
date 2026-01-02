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
            'receipt_number' => 'nullable|string',
            'verified_by' => 'nullable|string',
            'amount_received' => 'nullable|numeric',
            'bank_reference' => 'nullable|string',
            'transfer_date' => 'nullable|date',
            'amount_transferred' => 'nullable|numeric',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
