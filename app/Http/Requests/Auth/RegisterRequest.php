<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('gender') === '') {
            $this->merge(['gender' => null]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['required', 'string', 'max:30'],
            'age' => ['required', 'integer', 'min:0', 'max:120'],
            'role' => ['required', Rule::in(['patient', 'doctor'])],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
