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

        $this->merge([
            'email' => $email,
            'gender' => $this->input('gender') === '' ? null : $this->input('gender'),
            'blood_type' => $this->input('blood_type') === '' ? null : $this->input('blood_type'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $existingPatient = User::query()
            ->where('email', (string) $this->input('email'))
            ->where('role', 'patient')
            ->exists();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'age' => ['required', 'integer', 'min:0', 'max:120'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'blood_type' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'password' => [$existingPatient ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'date' => [
                'required',
                'date',
                'after_or_equal:today',
                'before_or_equal:'.now()->addDays(AppointmentBooking::DAYS - 1)->toDateString(),
            ],
            'time' => ['required', 'date_format:H:i'],
            'visit_type' => ['required', Rule::in(['check-up', 'consultation', 'follow-up', 'emergency'])],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
