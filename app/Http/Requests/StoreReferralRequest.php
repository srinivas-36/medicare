<?php

namespace App\Http\Requests;

use App\Models\Doctor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'doctor';
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $senderId = $this->user()?->doctor?->id;

        return [
            'to_doctor_id' => [
                'required',
                'integer',
                Rule::exists('doctors', 'id')->where(function ($query) use ($senderId) {
                    $query->where('approval_status', 'approved');

                    if ($senderId) {
                        $query->where('id', '!=', $senderId);
                    }
                }),
                function (string $attribute, mixed $value, \Closure $fail) use ($senderId): void {
                    if ($senderId !== null && (int) $value === (int) $senderId) {
                        $fail('You cannot refer a patient to yourself.');

                        return;
                    }

                    $doctor = Doctor::query()->with('user')->find($value);

                    if (
                        ! $doctor
                        || $doctor->approval_status !== 'approved'
                        || $doctor->user?->role !== 'doctor'
                        || ! $doctor->user?->is_active
                    ) {
                        $fail('Choose an approved doctor who is active.');
                    }
                },
            ],
            'reason' => ['required', 'string', 'max:5000'],
        ];
    }
}
