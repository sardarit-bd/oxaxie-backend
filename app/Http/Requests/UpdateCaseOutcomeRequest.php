<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCaseOutcomeRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'outcome_type' => 'sometimes|string|in:won,settled,lost,dropped',
            'outcome_summary' => 'sometimes|string|min:10|max:10000',
            'money_saved' => 'nullable|string|max:255',
            'money_recovered' => 'nullable|string|max:255',
            'court_avoided' => 'nullable|boolean',
            'hired_attorney' => 'nullable|boolean',
            'ai_helpfulness_rating' => 'nullable|integer|min:0|max:5',
            'feedback_text' => 'nullable|string|max:10000',
            'would_recommend' => 'nullable|boolean',
            'testimonial_consent' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'outcome_type.in' => 'Invalid outcome type selected',
            'outcome_summary.min' => 'Outcome summary must be at least 10 characters',
            'outcome_summary.max' => 'Outcome summary cannot exceed 10,000 characters',
            'money_saved.max' => 'Money saved value is too long',
            'money_recovered.max' => 'Money recovered value is too long',
            'ai_helpfulness_rating.integer' => 'Rating must be a number',
            'ai_helpfulness_rating.min' => 'Rating must be at least 0',
            'ai_helpfulness_rating.max' => 'Rating must be at most 5',
            'feedback_text.max' => 'Feedback text cannot exceed 10,000 characters',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()->toArray()
            ], 422)
        );
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];
        
        if ($this->has('money_saved')) {
            $merge['money_saved'] = $this->money_saved === '' ? null : $this->money_saved;
        }
        
        if ($this->has('money_recovered')) {
            $merge['money_recovered'] = $this->money_recovered === '' ? null : $this->money_recovered;
        }
        
        if ($this->has('ai_helpfulness_rating')) {
            $merge['ai_helpfulness_rating'] = $this->ai_helpfulness_rating === '' ? null : $this->ai_helpfulness_rating;
        }
        
        if ($this->has('feedback_text')) {
            $merge['feedback_text'] = $this->feedback_text === '' ? null : $this->feedback_text;
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }
}