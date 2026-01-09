<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreCaseOutcomeRequest extends FormRequest
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
            'all_case_id' => 'required|uuid|exists:all_cases,id',
            'outcome_type' => 'required|string|in:won,settled,lost,dropped',
            'outcome_summary' => 'required|string|min:10|max:10000',
            'money_saved' => 'nullable|string|max:255',
            'money_recovered' => 'nullable|string|max:255',
            'court_avoided' => 'nullable|boolean',
            'hired_attorney' => 'nullable|boolean',
            'ai_helpfulness_rating' => 'nullable|integer|min:0|max:5',
            'feedback_text' => 'nullable|string|max:10000',
            'would_recommend' => 'nullable|boolean',
            'testimonial_consent' => 'nullable|boolean',
            'days_to_resolution' => 'nullable|integer|min:0|max:36500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'all_case_id.required' => 'Case ID is required',
            'all_case_id.exists' => 'The selected case does not exist',
            'outcome_type.required' => 'Please select an outcome type',
            'outcome_type.in' => 'Invalid outcome type selected',
            'outcome_summary.required' => 'Please provide an outcome summary',
            'outcome_summary.min' => 'Outcome summary must be at least 10 characters',
            'outcome_summary.max' => 'Outcome summary cannot exceed 10,000 characters',
            'money_saved.max' => 'Money saved value is too long',
            'money_recovered.max' => 'Money recovered value is too long',
            'ai_helpfulness_rating.integer' => 'Rating must be a number',
            'ai_helpfulness_rating.min' => 'Rating must be at least 0',
            'ai_helpfulness_rating.max' => 'Rating must be at most 5',
            'feedback_text.max' => 'Feedback text cannot exceed 10,000 characters',
            'days_to_resolution.integer' => 'Days to resolution must be a number',
            'days_to_resolution.min' => 'Days to resolution cannot be negative',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'all_case_id' => 'case ID',
            'outcome_type' => 'outcome type',
            'outcome_summary' => 'outcome summary',
            'money_saved' => 'money saved',
            'money_recovered' => 'money recovered',
            'court_avoided' => 'court avoided',
            'hired_attorney' => 'hired attorney',
            'ai_helpfulness_rating' => 'AI helpfulness rating',
            'feedback_text' => 'feedback text',
            'would_recommend' => 'would recommend',
            'testimonial_consent' => 'testimonial consent',
            'days_to_resolution' => 'days to resolution',
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

        $this->merge([
            'money_saved' => $this->money_saved === '' ? null : $this->money_saved,
            'money_recovered' => $this->money_recovered === '' ? null : $this->money_recovered,
            'court_avoided' => $this->court_avoided ?? false,
            'hired_attorney' => $this->hired_attorney ?? false,
            'ai_helpfulness_rating' => $this->ai_helpfulness_rating === '' ? null : $this->ai_helpfulness_rating,
            'feedback_text' => $this->feedback_text === '' ? null : $this->feedback_text,
            'testimonial_consent' => $this->testimonial_consent ?? false,
        ]);
    }
}