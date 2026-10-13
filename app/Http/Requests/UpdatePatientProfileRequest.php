<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdatePatientProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'patient';
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('gender') === '') {
            $this->merge(['gender' => null]);
        }

        $notes = [];

        foreach (['bp', 'diabetic', 'hyperthyroidism'] as $key) {
            $record = $this->input($key, []);

            if (! is_array($record)) {
                $record = [];
            }

            if (($record['value'] ?? null) === '') {
                $record['value'] = null;
            }

            $notes[$key] = $record;
        }

        $this->merge($notes);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'age' => ['required', 'integer', 'min:0', 'max:120'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'bloodType' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'bp.value' => ['nullable', 'string', 'max:50'],
            'bp.known' => ['nullable'],
            'diabetic.value' => ['nullable', Rule::in(['yes', 'no'])],
            'diabetic.known' => ['nullable'],
            'hyperthyroidism.value' => ['nullable', Rule::in(['yes', 'no'])],
            'hyperthyroidism.known' => ['nullable'],
        ];
    }
}
