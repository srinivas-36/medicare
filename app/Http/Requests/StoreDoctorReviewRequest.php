<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('reviewText')) {
            $this->merge([
                'reviewText' => trim((string) $this->input('reviewText')),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'reviewText' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rating.required' => 'Choose a star rating.',
            'rating.between' => 'Choose a star rating from 1 to 5.',
            'reviewText.required' => 'Write your review before submitting.',
            'reviewText.max' => 'Keep the review to 1000 characters.',
        ];
    }
}
