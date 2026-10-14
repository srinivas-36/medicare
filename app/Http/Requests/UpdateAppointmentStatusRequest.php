<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['doctor', 'receptionist'], true);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $allowed = $this->user()?->role === 'doctor'
            ? ['confirmed', 'checked_in', 'completed', 'cancelled', 'no_show']
            : ['confirmed', 'checked_in', 'cancelled', 'no_show'];

        return [
            'status' => ['required', Rule::in($allowed)],
            'cancel_reason' => ['nullable', 'string', 'max:255', 'required_if:status,cancelled'],
        ];
    }
}
