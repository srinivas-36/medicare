<?php

namespace App\Http\Requests;

use App\Services\AppointmentBooking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePatientAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'patient';
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $file = ['file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:10240'];

        return [
            'appointment_date' => [
                'required',
                'date',
                'after_or_equal:today',
                'before_or_equal:'.AppointmentBooking::bookableUntil()->toDateString(),
            ],
            'slot_day' => ['required', 'string', 'max:20'],
            'starting_time' => ['required', 'date_format:H:i'],
            'ending_time' => ['required', 'date_format:H:i'],
            'health_issues' => ['nullable', 'string', 'max:2000'],
            'symptom_duration' => ['nullable', 'string', 'max:80'],
            'current_medicines' => ['nullable', 'string', 'max:500'],
            'blood_report_name' => ['nullable', 'string', 'max:120'],
            'blood_reports' => ['nullable', 'array', 'max:8'],
            'blood_reports.*' => $file,
            'xray_report_name' => ['nullable', 'string', 'max:120'],
            'xray_reports' => ['nullable', 'array', 'max:8'],
            'xray_reports.*' => $file,
            'other_report_name' => ['nullable', 'string', 'max:120', 'required_with:other_reports'],
            'other_reports' => ['nullable', 'array', 'max:8'],
            'other_reports.*' => $file,
        ];
    }
}
