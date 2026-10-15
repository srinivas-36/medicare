<?php

namespace App\Support;

use App\Models\Doctor;
use App\Models\User;
use App\Services\AppointmentBooking;
use Illuminate\Support\Carbon;

class ClinicSchedule
{
    /**
     * @return list<array{day: string, weekday: string, start: string, end: string, from: string, to: string}>
     */
    public static function hours(Doctor $doctor): array
    {
        $doctor->loadMissing('timeSlots');

        $order = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        return $doctor->timeSlots
            ->filter(fn ($slot) => $slot->is_available)
            ->map(fn ($slot) => [
                'day' => strtolower((string) $slot->day),
                'start' => substr(AppointmentBooking::normalizeTime((string) $slot->starting_time), 0, 5),
                'end' => substr(AppointmentBooking::normalizeTime((string) $slot->ending_time), 0, 5),
            ])
            ->sortBy(fn (array $slot) => sprintf('%02d-%s', array_search($slot['day'], $order, true) ?: 0, $slot['start']))
            ->map(fn (array $slot) => [
                'day' => ucfirst($slot['day']),
                'weekday' => $slot['day'],
                'start' => $slot['start'],
                'end' => $slot['end'],
                'from' => Carbon::parse($slot['start'])->format('g:i A'),
                'to' => Carbon::parse($slot['end'])->format('g:i A'),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array{weekday: string, start: string, end: string, from: string, to: string}>  $hours
     * @return array<string, list<array{period: string, range: string, slots: list<array{value: string, label: string, endLabel: string}>}>>
     */
    public static function days(Doctor $doctor, AppointmentBooking $booking, ?array $hours = null): array
    {
        $hours ??= self::hours($doctor);
        $days = [];

        foreach ($booking->calendar($doctor) as $date => $slots) {
            $weekday = strtolower(Carbon::parse($date)->format('l'));
            $periods = array_values(array_filter(
                $hours,
                fn (array $hour) => $hour['weekday'] === $weekday,
            ));
            $groups = [];
            $used = [];

            foreach ($periods as $period) {
                $matches = [];

                foreach ($slots as $index => $slot) {
                    if ($slot['start'] >= $period['start'] && $slot['end'] <= $period['end']) {
                        $matches[] = self::visitChoice($slot);
                        $used[$index] = true;
                    }
                }

                if ($matches === []) {
                    continue;
                }

                $groups[] = [
                    'period' => self::periodName($period['start']),
                    'range' => $period['from'].' – '.$period['to'],
                    'slots' => $matches,
                ];
            }

            $rest = [];

            foreach ($slots as $index => $slot) {
                if (! isset($used[$index])) {
                    $rest[] = self::visitChoice($slot);
                }
            }

            if ($rest !== []) {
                $groups[] = [
                    'period' => 'Open',
                    'range' => '',
                    'slots' => $rest,
                ];
            }

            if ($groups !== []) {
                $days[$date] = $groups;
            }
        }

        return $days;
    }

    /**
     * @param  array<string, list<array{slots: list<array{value: string}>}>>  $days
     */
    public static function firstOpenDate(array $days): string
    {
        $date = array_key_first($days);

        return is_string($date) ? $date : '';
    }

    /**
     * @param  array<string, list<array{slots: list<array{value: string}>}>>  $days
     */
    public static function slotStarting(array $days, string $date, string $start): string
    {
        $start = substr(AppointmentBooking::normalizeTime($start), 0, 5);

        foreach ($days[$date] ?? [] as $group) {
            foreach ($group['slots'] as $slot) {
                if (str_starts_with($slot['value'], $start.'|')) {
                    return $slot['value'];
                }
            }
        }

        return '';
    }

    public static function visibleMonth(mixed $month, string $selectedDate, string $minDate, string $maxDate): Carbon
    {
        $parsed = null;

        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month)) {
            $parsed = Carbon::createFromFormat('Y-m-d', $month.'-01');
        } elseif ($selectedDate !== '') {
            $parsed = Carbon::parse($selectedDate);
        }

        $visible = ($parsed ?: now())->copy()->startOfMonth();
        $earliest = Carbon::parse($minDate)->startOfMonth();
        $latest = Carbon::parse($maxDate)->startOfMonth();

        if ($visible->lt($earliest)) {
            return $earliest;
        }

        if ($visible->gt($latest)) {
            return $latest;
        }

        return $visible;
    }

    /**
     * @return list<array{id: int, name: string, email: string|null, phone: string|null, age: int|null, gender: string|null, blood_type: string|null}>
     */
    public static function patients(?string $search = null, ?string $selectedId = null, int $limit = 40): array
    {
        $search = trim((string) $search);
        $query = User::query()->where('role', 'patient')->orderBy('name');

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($inner) use ($like) {
                $inner->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like);
            });
        }

        $patients = $query->limit($limit)->get(['id', 'name', 'email', 'phone', 'gender']);

        if ($selectedId !== null && $selectedId !== '' && $patients->doesntContain('id', (int) $selectedId)) {
            $selected = User::query()->where('role', 'patient')->find($selectedId, ['id', 'name', 'email', 'phone', 'gender']);

            if ($selected) {
                $patients->prepend($selected);
            }
        }

        $patients->load('patient_profile:id,user_id,age,blood_type');

        return $patients->map(fn (User $patient) => [
            'id' => $patient->id,
            'name' => $patient->name,
            'email' => $patient->email,
            'phone' => $patient->phone,
            'age' => $patient->patient_profile?->age,
            'gender' => $patient->gender,
            'blood_type' => $patient->patient_profile?->blood_type,
        ])->values()->all();
    }

    /**
     * @param  array{start: string, end: string}  $slot
     * @return array{value: string, label: string, endLabel: string}
     */
    private static function visitChoice(array $slot): array
    {
        return [
            'value' => $slot['start'].'|'.$slot['end'],
            'label' => Carbon::parse($slot['start'])->format('g:i A'),
            'endLabel' => Carbon::parse($slot['end'])->format('g:i A'),
        ];
    }

    private static function periodName(string $start): string
    {
        $hour = (int) substr($start, 0, 2);

        if ($hour < 12) {
            return 'Morning';
        }

        if ($hour < 17) {
            return 'Afternoon';
        }

        return 'Evening';
    }
}
