<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDoctorAvailabilityRequest extends FormRequest
{
    public const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    public function authorize(): bool
    {
        return $this->user()?->role === 'doctor';
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'timeSlots' => $this->filledRows($this->input('timeSlots'), ['day', 'startingTime', 'endingTime']),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
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
            $byDay = [];

            foreach ($this->input('timeSlots', []) as $index => $slot) {
                $start = $slot['startingTime'] ?? null;
                $end = $slot['endingTime'] ?? null;
                $day = strtolower((string) ($slot['day'] ?? ''));

                if (! is_string($start) || ! is_string($end) || $start >= $end) {
                    $validator->errors()->add("timeSlots.$index.endingTime", 'The ending time must be after the starting time.');

                    continue;
                }

                $byDay[$day][] = ['index' => $index, 'start' => $start, 'end' => $end];
            }

            foreach ($byDay as $ranges) {
                usort($ranges, fn (array $left, array $right) => strcmp($left['start'], $right['start']));
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
        });
    }

    /**
     * @param  array<int, mixed>|null  $rows
     * @param  list<string>  $keys
     * @return list<array<string, mixed>>
     */
    private function filledRows(mixed $rows, array $keys): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter($rows, function ($row) use ($keys) {
            if (! is_array($row)) {
                return false;
            }

            foreach ($keys as $key) {
                if (filled($row[$key] ?? null)) {
                    return true;
                }
            }

            return false;
        }));
    }
}
