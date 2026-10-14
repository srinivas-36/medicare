<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDoctorProfileRequest extends FormRequest
{
    public const DAYS = [
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
    ];

    public function authorize(): bool
    {
        return $this->user()?->role === 'doctor';
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('gender') === '') {
            $this->merge(['gender' => null]);
        }

        $settings = [
            'qualifications' => $this->filledRows($this->input('qualifications'), ['degree', 'university', 'startingDate', 'endingDate']),
            'experiences' => $this->filledRows($this->input('experiences'), ['position', 'hospital', 'startingDate', 'endingDate']),
            'timeSlots' => $this->filledRows($this->input('timeSlots'), ['day', 'startingTime', 'endingTime']),
        ];

        foreach (['appointmentDuration', 'appointmentBuffer'] as $field) {
            $value = $this->input($field);

            if ($value === null || $value === '') {
                continue;
            }

            $settings[$field] = (int) $value;
        }

        $this->merge($settings);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9]{10}$/'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'photo' => ['nullable', 'image', 'max:2048'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'ticketPrice' => ['required', 'regex:/^[0-9]+(\.[0-9]{1,2})?$/', 'numeric', 'min:0'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'about' => ['nullable', 'string', 'max:5000'],
            'qualifications' => ['array'],
            'qualifications.*.degree' => ['required', 'string', 'max:255'],
            'qualifications.*.university' => ['required', 'string', 'max:255'],
            'qualifications.*.startingDate' => ['nullable', 'date'],
            'qualifications.*.endingDate' => ['nullable', 'date'],
            'experiences' => ['array'],
            'experiences.*.position' => ['required', 'string', 'max:255'],
            'experiences.*.hospital' => ['required', 'string', 'max:255'],
            'experiences.*.startingDate' => ['required', 'date'],
            'experiences.*.endingDate' => ['nullable', 'date'],
            'timeSlots' => ['array'],
            'timeSlots.*.day' => ['required', Rule::in(self::DAYS)],
            'timeSlots.*.startingTime' => ['required', 'date_format:H:i'],
            'timeSlots.*.endingTime' => ['required', 'date_format:H:i'],
            'timeSlots.*.isAvailable' => ['nullable', 'boolean'],
            'appointmentDuration' => ['nullable', 'integer', Rule::in([15, 20, 30, 45, 60, 90])],
            'appointmentBuffer' => ['nullable', 'integer', Rule::in([0, 5, 10, 15, 20, 30])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('timeSlots', []) as $index => $slot) {
                $start = $slot['startingTime'] ?? null;
                $end = $slot['endingTime'] ?? null;

                if (! is_string($start) || ! is_string($end) || $start === '' || $end === '') {
                    continue;
                }

                if ($start >= $end) {
                    $validator->errors()->add("timeSlots.$index.endingTime", 'The ending time must be after the starting time.');
                }
            }

            $byDay = [];

            foreach ($this->input('timeSlots', []) as $index => $slot) {
                $start = $slot['startingTime'] ?? null;
                $end = $slot['endingTime'] ?? null;
                $day = strtolower((string) ($slot['day'] ?? ''));

                if (! is_string($start) || ! is_string($end) || $start === '' || $end === '' || $start >= $end || $day === '') {
                    continue;
                }

                $byDay[$day][] = [
                    'index' => $index,
                    'start' => $start,
                    'end' => $end,
                ];
            }

            foreach ($byDay as $ranges) {
                usort($ranges, fn (array $a, array $b) => strcmp($a['start'], $b['start']));
                $previousEnd = null;

                foreach ($ranges as $range) {
                    if ($previousEnd !== null && $range['start'] < $previousEnd) {
                        $validator->errors()->add("timeSlots.{$range['index']}.startingTime", 'This range overlaps another range on the same day.');
                    }

                    if ($previousEnd === null || $range['end'] > $previousEnd) {
                        $previousEnd = $range['end'];
                    }
                }
            }

            foreach (['qualifications', 'experiences'] as $group) {
                foreach ($this->input($group, []) as $index => $row) {
                    $start = $row['startingDate'] ?? null;
                    $end = $row['endingDate'] ?? null;

                    if (! $start || ! $end) {
                        continue;
                    }

                    if ($end < $start) {
                        $validator->errors()->add("$group.$index.endingDate", 'The ending date must be on or after the starting date.');
                    }
                }
            }
        });
    }

    /**
     * @param  list<string>  $keys
     * @return list<array<string, mixed>>
     */
    private function filledRows(mixed $rows, array $keys): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $filled = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $hasValue = false;

            foreach ($keys as $key) {
                if (filled($row[$key] ?? null)) {
                    $hasValue = true;
                    break;
                }
            }

            if (! $hasValue) {
                continue;
            }

            $filled[] = $row;
        }

        return $filled;
    }
}
