<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\AppointmentBooking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreDoctorPatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'doctor';
    }

    protected function prepareForValidation(): void
    {
        $email = strtolower(trim((string) $this->input('email')));

        $age = trim((string) $this->input('age'));

        $this->merge([
            'email' => $email === '' ? null : $email,
            'gender' => $this->input('gender') === '' ? null : $this->input('gender'),
            'blood_type' => $this->input('blood_type') === '' ? null : $this->input('blood_type'),
            'age' => $age === '' ? null : $age,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $existingAccount = $this->input('mode') === 'existing';
        $existingPatient = ! $existingAccount && User::query()
            ->where('email', (string) $this->input('email'))
            ->where('role', 'patient')
            ->exists();

        return [
            'mode' => ['nullable', Rule::in(['new', 'existing'])],
            'patient_id' => [
                $existingAccount ? 'required' : 'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', 'patient'),
            ],
            'name' => [$existingAccount ? 'nullable' : 'required', 'string', 'max:255'],
            'email' => [$existingAccount ? 'nullable' : 'required', 'string', 'email', 'max:255'],
            'phone' => [$existingAccount ? 'nullable' : 'required', 'string', 'max:30'],
            'age' => [$existingAccount ? 'nullable' : 'required', 'string', 'regex:/^[0-9]{2}$/'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'blood_type' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'password' => [($existingAccount || $existingPatient) ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'date' => [
                'required',
                'date',
                'after_or_equal:today',
                'before_or_equal:'.AppointmentBooking::bookableUntil()->toDateString(),
            ],
            'time' => ['required', 'date_format:H:i'],
            'visit_type' => ['required', Rule::in(['check-up', 'consultation', 'follow-up', 'emergency'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'age.required' => 'Enter the age.',
            'age.regex' => 'Enter a 2-digit age using numbers only.',
        ];
    }
}
