<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentReportsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('appointment');
        $user = $this->user();

        return $user?->role === 'patient'
            && $appointment instanceof Appointment
            && (int) $appointment->patient_id === (int) $user->id
            && $appointment->status !== 'cancelled';
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $file = ['file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:5120'];

        return [
            'blood_reports' => ['nullable', 'array', 'max:8'],
            'blood_reports.*' => $file,
            'xray_reports' => ['nullable', 'array', 'max:8'],
            'xray_reports.*' => $file,
            'other_report_name' => ['nullable', 'string', 'max:120', 'required_with:other_reports'],
            'other_reports' => ['nullable', 'array', 'max:8'],
            'other_reports.*' => $file,
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->hasFile('blood_reports') || $this->hasFile('xray_reports') || $this->hasFile('other_reports')) {
                return;
            }

            $validator->errors()->add('blood_reports', 'Choose at least one report to upload.');
        });
    }
}
