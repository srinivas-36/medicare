<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('appointment');
        $user = $this->user();

        if (! $user || ! $appointment instanceof Appointment) {
            return false;
        }

        if ($user->role === 'patient') {
            return (int) $appointment->patient_id === (int) $user->id;
        }

        if ($user->role === 'doctor') {
            return (int) $user->doctor?->id === (int) $appointment->doctor_id;
        }

        return false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('body')) {
            $this->merge([
                'body' => trim((string) $this->input('body')),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
        ];
    }
}
