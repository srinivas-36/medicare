<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\AppointmentBooking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreReceptionistAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'receptionist' && $this->user()->receptionist?->doctor_id !== null;
    }

    protected function prepareForValidation(): void
    {
        $slot = (string) $this->input('slot', '');

        if (preg_match('/^(\d{2}:\d{2})\|(\d{2}:\d{2})$/', $slot, $matches)) {
            $this->merge([
                'starting_time' => $matches[1],
                'ending_time' => $matches[2],
            ]);
        }

        if (! in_array($this->input('patient_mode'), ['existing', 'new'], true)) {
            $this->merge(['patient_mode' => 'existing']);
        }

        $email = strtolower(trim((string) $this->input('email')));

        if ($email !== '') {
            $this->merge(['email' => $email]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $creating = $this->input('patient_mode') === 'new';
        $existingPatient = $creating && User::query()
            ->where('email', (string) $this->input('email'))
            ->where('role', 'patient')
            ->exists();

        return [
            'patient_mode' => ['required', Rule::in(['existing', 'new'])],
            'patient_id' => [
                $creating ? 'nullable' : 'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'patient'),
            ],
            'name' => [$creating ? 'required' : 'nullable', 'string', 'max:255'],
            'email' => [$creating ? 'required' : 'nullable', 'string', 'email', 'max:255'],
            'phone' => [$creating ? 'required' : 'nullable', 'string', 'max:30'],
            'age' => [$creating ? 'required' : 'nullable', 'integer', 'min:0', 'max:120'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'password' => [($creating && ! $existingPatient) ? 'required' : 'nullable', 'confirmed', Password::defaults()],
            'date' => [
                'required',
                'date',
                'after_or_equal:today',
                'before_or_equal:'.AppointmentBooking::bookableUntil()->toDateString(),
            ],
            'slot' => ['required', 'regex:/^\d{2}:\d{2}\|\d{2}:\d{2}$/'],
            'starting_time' => ['required', 'date_format:H:i'],
            'ending_time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:255'],
            'visit_type' => ['required', Rule::in(['check-up', 'consultation', 'follow-up', 'emergency'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slot.required' => 'Choose one of the doctor’s saved time slots.',
            'slot.regex' => 'Choose one of the doctor’s saved time slots.',
            'starting_time.required' => 'Choose one of the doctor’s saved time slots.',
        ];
    }
}
