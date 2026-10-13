<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'doctor';
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'clinical_notes' => ['nullable', 'string', 'max:20000'],
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'plan' => ['nullable', 'string', 'max:20000'],
        ];
    }
}
