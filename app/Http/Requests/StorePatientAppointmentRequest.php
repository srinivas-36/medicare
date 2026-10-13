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
        $file = ['file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:5120'];

        return [
            'appointment_date' => [
                'required',
                'date',
                'after_or_equal:today',
                'before_or_equal:'.now()->addDays(AppointmentBooking::DAYS - 1)->toDateString(),
            ],
            'slot_day' => ['required', 'string', 'max:20'],
            'starting_time' => ['required', 'date_format:H:i'],
            'ending_time' => ['required', 'date_format:H:i'],
            'health_issues' => ['nullable', 'string', 'max:2000'],
            'blood_reports' => ['nullable', 'array', 'max:8'],
            'blood_reports.*' => $file,
            'xray_reports' => ['nullable', 'array', 'max:8'],
            'xray_reports.*' => $file,
            'other_report_name' => ['nullable', 'string', 'max:120', 'required_with:other_reports'],
            'other_reports' => ['nullable', 'array', 'max:8'],
            'other_reports.*' => $file,
        ];
    }
}
