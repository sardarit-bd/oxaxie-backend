<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AllCaseRequest extends FormRequest
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
        $rules = [
            'issue_type' => [
                'required',
                Rule::in([
                    'landlord_tenant',
                    'employment',
                    'contracts',
                    'consumer_rights',
                    'family',
                    'other'
                ])
            ],
            'location_city' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[\p{L}\s\-\.]+$/u'
            ],
            'location_state' => [
                'required',
                'string',
                'max:100',
                'regex:/^[\p{L}\s\-\.]+$/u'
            ],
            'location_country' => [
                'required',
                'string',
                'size:2',
                'uppercase',
                'regex:/^[A-Z]{2}$/'
            ],
            'situation_description' => [
                'required',
                'string',
                'min:50',
                'max:10000'
            ],
            'status' => [
                'sometimes',
                Rule::in(['active', 'resolved', 'archived'])
            ],
            'resolution_type' => [
                'nullable',
                'required_if:status,resolved',
                Rule::in(['won', 'settled', 'lost', 'dropped'])
            ],
            'resolved_at' => [
                'nullable',
                'required_if:status,resolved',
                'date',
                'before_or_equal:now'
            ]
        ];

  
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['issue_type'][0] = 'sometimes';
            $rules['location_state'][0] = 'sometimes';
            $rules['location_country'][0] = 'sometimes';
            $rules['situation_description'][0] = 'sometimes';
        }

        return $rules;
    }


    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // issue_type messages
            'issue_type.required' => 'Please select the type of legal issue you are facing.',
            'issue_type.in' => 'The selected issue type is not valid. Please choose from the available options.',

            // location_city messages
            'location_city.string' => 'The city name must be text.',
            'location_city.max' => 'The city name cannot exceed 255 characters.',
            'location_city.regex' => 'The city name can only contain letters, spaces, hyphens, and periods.',

            // location_state messages
            'location_state.required' => 'Please provide the state or province where this case is located.',
            'location_state.string' => 'The state name must be text.',
            'location_state.max' => 'The state name cannot exceed 100 characters.',
            'location_state.regex' => 'The state name can only contain letters, spaces, hyphens, and periods.',

            // location_country messages
            'location_country.required' => 'Please provide the country code for this case.',
            'location_country.string' => 'The country code must be text.',
            'location_country.size' => 'The country code must be exactly 2 characters (ISO 3166-1 alpha-2 format).',
            'location_country.uppercase' => 'The country code must be in uppercase letters.',
            'location_country.regex' => 'The country code must be 2 uppercase letters (e.g., US, GB, CA).',

            // situation_description messages
            'situation_description.required' => 'Please describe your situation in detail.',
            'situation_description.string' => 'The situation description must be text.',
            'situation_description.min' => 'Please provide at least 50 characters to adequately describe your situation.',
            'situation_description.max' => 'The situation description cannot exceed 10,000 characters.',

            // status messages
            'status.in' => 'The status must be either active, resolved, or archived.',

            // resolution_type messages
            'resolution_type.required_if' => 'Please specify how the case was resolved when marking it as resolved.',
            'resolution_type.in' => 'The resolution type must be won, settled, lost, or dropped.',

            // resolved_at messages
            'resolved_at.required_if' => 'Please provide the resolution date when marking a case as resolved.',
            'resolved_at.date' => 'The resolution date must be a valid date.',
            'resolved_at.before_or_equal' => 'The resolution date cannot be in the future.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize country code to uppercase
        if ($this->has('location_country')) {
            $this->merge([
                'location_country' => strtoupper($this->location_country)
            ]);
        }

        // Trim whitespace from text fields
        if ($this->has('location_city')) {
            $this->merge([
                'location_city' => trim($this->location_city)
            ]);
        }

        if ($this->has('location_state')) {
            $this->merge([
                'location_state' => trim($this->location_state)
            ]);
        }

        if ($this->has('situation_description')) {
            $this->merge([
                'situation_description' => trim($this->situation_description)
            ]);
        }
    }


     /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        if ($this->status === 'resolved' && !$this->resolved_at) {
            $this->merge([
                'resolved_at' => now()
            ]);
        }
    }

}
