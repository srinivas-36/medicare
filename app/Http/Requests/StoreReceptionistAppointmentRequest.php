<?php

namespace App\Http\Requests;

use App\Services\AppointmentBooking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReceptionistAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'receptionist';
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'doctor_id' => [
                'required',
                'integer',
                Rule::exists('doctors', 'id')->where('approval_status', 'approved'),
            ],
            'patient_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', 'patient'),
            ],
            'date' => [
                'required',
                'date',
                'after_or_equal:today',
                'before_or_equal:'.now()->addDays(AppointmentBooking::DAYS - 1)->toDateString(),
            ],
            'time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:255'],
            'visit_type' => ['required', Rule::in(['check-up', 'consultation', 'follow-up', 'emergency'])],
        ];
    }
}
