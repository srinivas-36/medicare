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
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'phone' => trim((string) $this->input('phone')),
            'age' => trim((string) $this->input('age')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z]+(?:[ .\'-][A-Za-z]+)*$/'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'regex:/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults(), 'regex:/^(?=.*[A-Za-z])(?=.*\d).{8,}$/'],
            'phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'age' => ['required', 'string', 'regex:/^[0-9]{2}$/'],
            'role' => ['required', Rule::in(['patient', 'doctor'])],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter your full name.',
            'name.regex' => 'Use letters, spaces, apostrophes, or hyphens only.',
            'email.required' => 'Enter your email address.',
            'email.email' => 'Enter a valid email address.',
            'email.regex' => 'Enter a valid email address.',
            'password.required' => 'Create a password.',
            'password.confirmed' => 'Passwords do not match.',
            'password.regex' => 'Use at least 8 characters, including a letter and a number.',
            'phone.required' => 'Enter your phone number.',
            'phone.regex' => 'Enter a 10-digit phone number using numbers only.',
            'age.required' => 'Enter your age.',
            'age.regex' => 'Enter a 2-digit age using numbers only.',
            'role.required' => 'Choose patient or doctor.',
            'role.in' => 'Choose patient or doctor.',
            'gender.required' => 'Select a gender.',
            'gender.in' => 'Select a gender.',
            'photo.image' => 'Upload a JPG or PNG photo.',
            'photo.max' => 'Photo must be 2 MB or smaller.',
        ];
    }
}
