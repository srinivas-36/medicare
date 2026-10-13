<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reception desks still pick a 30-minute step inside a weekly window.
 * The public booking page reserves the whole window (starting_time to ending_time).
 * A non-cancelled appointment blocks any range it overlaps.
 * Creating a visit locks the doctor row so two patients cannot take the same window.
 */
class AppointmentBooking
{
    public const INTERVAL_MINUTES = 30;

    public const DAYS = 14;

    /**
     * @return array<string, list<string>>
     */
    public function slotsForNextDays(Doctor $doctor, int $days = self::DAYS): array
    {
        $slots = [];
        $start = now()->startOfDay();

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $start->copy()->addDays($offset);
            $slots[$date->toDateString()] = $this->openTimes($doctor, $date);
        }

        return $slots;
    }

    /**
     * @return list<string> Times as H:i
     */
    public function openTimes(Doctor $doctor, Carbon $date, bool $fresh = false): array
    {
        $day = $date->copy()->startOfDay();
        $today = now()->startOfDay();

        if ($day->lt($today) || $day->gt($today->copy()->addDays(self::DAYS - 1))) {
            return [];
        }

        $user = $doctor->relationLoaded('user') ? $doctor->user : $doctor->user()->first();

        if ($doctor->approval_status !== 'approved' || ! $user || ! $user->is_active) {
            return [];
        }

        $weekday = strtolower($day->format('l'));
        $windows = $this->windows($doctor, $weekday, $fresh);

        if ($windows->isEmpty()) {
            return [];
        }

        $appointments = $this->appointmentsOn($doctor, $day->toDateString(), $fresh);
        $times = [];

        foreach ($windows as $window) {
            $cursor = Carbon::parse($day->toDateString().' '.self::normalizeTime((string) $window->starting_time));
            $end = Carbon::parse($day->toDateString().' '.self::normalizeTime((string) $window->ending_time));

            while ($cursor->lt($end)) {
                $sliceEnd = $cursor->copy()->addMinutes(self::INTERVAL_MINUTES);

                if ($cursor->greaterThan(now()) && ! $this->rangeTaken($appointments, $day->toDateString(), $cursor, $sliceEnd)) {
                    $times[$cursor->format('H:i:s')] = $cursor->format('H:i');
                }

                $cursor->addMinutes(self::INTERVAL_MINUTES);
            }
        }

        ksort($times);

        return array_values($times);
    }

    public function create(
        User $patient,
        Doctor $doctor,
        string $date,
        string $time,
        ?string $reason,
        string $visitType,
        string $status,
    ): Appointment {
        if ($patient->role !== 'patient') {
            throw ValidationException::withMessages([
                'patient_id' => 'Choose a patient account.',
            ]);
        }

        return DB::transaction(function () use ($patient, $doctor, $date, $time, $reason, $visitType, $status) {
            $locked = Doctor::query()->whereKey($doctor->id)->lockForUpdate()->firstOrFail();
            $locked->setRelation('user', $locked->user()->first());

            $this->assertOpen($locked, $date, $time);

            return Appointment::query()->create([
                'patient_id' => $patient->id,
                'doctor_id' => $locked->id,
                'appointment_date' => Carbon::parse($date)->toDateString(),
                'appointment_time' => self::normalizeTime($time),
                'status' => $status,
                'reason' => $reason,
                'visit_type' => $visitType,
            ]);
        });
    }

    /**
     * Dates in the next 14 days. Each value is "fully booked" or a list of
     * taken keys "09:00-12:00", which the booking page script already understands.
     *
     * @return array<string, string|list<string>>
     */
    public function blockedWindows(Doctor $doctor): array
    {
        $blocked = [];
        $start = now()->startOfDay();

        for ($offset = 0; $offset < self::DAYS; $offset++) {
            $day = $start->copy()->addDays($offset);
            $date = $day->toDateString();
            $weekday = strtolower($day->format('l'));
            $windows = $this->windows($doctor, $weekday, false);
            $appointments = $this->appointmentsOn($doctor, $date, false);
            $taken = [];

            foreach ($windows as $window) {
                $starting = substr(self::normalizeTime((string) $window->starting_time), 0, 5);
                $ending = substr(self::normalizeTime((string) $window->ending_time), 0, 5);

                if ($this->rangeTaken(
                    $appointments,
                    $date,
                    Carbon::parse($date.' '.$starting),
                    Carbon::parse($date.' '.$ending),
                )) {
                    $taken[] = $starting.'-'.$ending;
                }
            }

            $blocked[$date] = $windows->isNotEmpty() && count($taken) === $windows->count()
                ? 'fully booked'
                : $taken;
        }

        return $blocked;
    }

    /**
     * @param  list<array{group_name: string, path: string, original_name: string}>  $reports
     */
    public function reserveWindow(
        User $patient,
        Doctor $doctor,
        string $date,
        string $startingTime,
        string $endingTime,
        ?string $reason,
        array $reports = [],
    ): Appointment {
        if ($patient->role !== 'patient') {
            throw ValidationException::withMessages([
                'appointment_date' => 'Choose a patient account.',
            ]);
        }

        return DB::transaction(function () use ($patient, $doctor, $date, $startingTime, $endingTime, $reason, $reports) {
            $locked = Doctor::query()->whereKey($doctor->id)->lockForUpdate()->firstOrFail();
            $locked->setRelation('user', $locked->user()->first());

            $this->assertWindow($locked, $date, $startingTime, $endingTime);

            $appointment = Appointment::query()->create([
                'patient_id' => $patient->id,
                'doctor_id' => $locked->id,
                'appointment_date' => Carbon::parse($date)->toDateString(),
                'appointment_time' => self::normalizeTime($startingTime),
                'ending_time' => self::normalizeTime($endingTime),
                'status' => 'pending',
                'reason' => $reason,
                'visit_type' => 'consultation',
                'payment_status' => 'unpaid',
                'ticket_price' => $locked->ticket_price,
            ]);

            foreach ($reports as $report) {
                $appointment->reports()->create($report);
            }

            if ($this->cents((string) $locked->ticket_price) === 0) {
                $appointment->forceFill([
                    'payment_status' => 'paid',
                    'paid_at' => now(),
                    'status' => 'confirmed',
                ])->save();
            }

            return $appointment;
        });
    }

    public function assertWindow(Doctor $doctor, string $date, string $startingTime, string $endingTime): void
    {
        try {
            $day = Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'appointment_date' => 'Choose a valid date.',
            ]);
        }

        $today = now()->startOfDay();

        if ($day->lt($today) || $day->gt($today->copy()->addDays(self::DAYS - 1))) {
            throw ValidationException::withMessages([
                'appointment_date' => 'Choose a date within the next 14 days.',
            ]);
        }

        if ($doctor->approval_status !== 'approved' || ! $doctor->user?->is_active) {
            throw ValidationException::withMessages([
                'appointment_date' => 'This doctor is not available for booking.',
            ]);
        }

        $start = substr(self::normalizeTime($startingTime), 0, 5);
        $end = substr(self::normalizeTime($endingTime), 0, 5);
        $weekday = strtolower($day->format('l'));

        $match = $this->windows($doctor, $weekday, true)->first(function (TimeSlot $slot) use ($start, $end) {
            return substr(self::normalizeTime((string) $slot->starting_time), 0, 5) === $start
                && substr(self::normalizeTime((string) $slot->ending_time), 0, 5) === $end;
        });

        if ($match === null) {
            throw ValidationException::withMessages([
                'starting_time' => 'That time is not available.',
            ]);
        }

        $dateString = $day->toDateString();

        if ($this->rangeTaken(
            $this->appointmentsOn($doctor, $dateString, true),
            $dateString,
            Carbon::parse($dateString.' '.$start),
            Carbon::parse($dateString.' '.$end),
        )) {
            throw ValidationException::withMessages([
                'starting_time' => 'That time is not available.',
            ]);
        }
    }

    public function cents(string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public function assertOpen(Doctor $doctor, string $date, string $time): void
    {
        try {
            $day = Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'date' => 'Choose a valid date.',
            ]);
        }

        $today = now()->startOfDay();

        if ($day->lt($today) || $day->gt($today->copy()->addDays(self::DAYS - 1))) {
            throw ValidationException::withMessages([
                'date' => 'Choose a date within the next 14 days.',
            ]);
        }

        if ($doctor->approval_status !== 'approved' || ! $doctor->user?->is_active) {
            throw ValidationException::withMessages([
                'date' => 'This doctor is not available for booking.',
            ]);
        }

        $available = $this->openTimes($doctor, $day, true);
        $needle = substr(self::normalizeTime($time), 0, 5);

        if (! in_array($needle, $available, true)) {
            throw ValidationException::withMessages([
                'time' => 'That time is not available.',
            ]);
        }
    }

    public static function normalizeTime(string $time): string
    {
        $time = trim($time);

        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time.':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}/', $time)) {
            return substr($time, 0, 8);
        }

        return Carbon::parse($time)->format('H:i:s');
    }

    /**
     * @return Collection<int, TimeSlot>
     */
    private function windows(Doctor $doctor, string $weekday, bool $fresh): Collection
    {
        if (! $fresh && $doctor->relationLoaded('timeSlots')) {
            return $doctor->timeSlots
                ->filter(fn (TimeSlot $slot) => strtolower((string) $slot->day) === $weekday && $slot->is_available)
                ->values();
        }

        return $doctor->timeSlots()
            ->where('is_available', true)
            ->get()
            ->filter(fn (TimeSlot $slot) => strtolower((string) $slot->day) === $weekday)
            ->values();
    }

    /**
     * @return Collection<int, Appointment>
     */
    private function appointmentsOn(Doctor $doctor, string $date, bool $fresh): Collection
    {
        $appointments = (! $fresh && $doctor->relationLoaded('appointments'))
            ? $doctor->appointments
            : $doctor->appointments()
                ->where('status', '!=', 'cancelled')
                ->whereDate('appointment_date', $date)
                ->get();

        return $appointments
            ->filter(function (Appointment $appointment) use ($date) {
                if ($appointment->status === 'cancelled') {
                    return false;
                }

                return $appointment->appointment_date->toDateString() === $date;
            })
            ->values();
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     */
    private function rangeTaken(Collection $appointments, string $date, Carbon $rangeStart, Carbon $rangeEnd): bool
    {
        foreach ($appointments as $appointment) {
            $start = Carbon::parse($date.' '.self::normalizeTime((string) $appointment->appointment_time));
            $end = filled($appointment->ending_time)
                ? Carbon::parse($date.' '.self::normalizeTime((string) $appointment->ending_time))
                : $start->copy()->addMinutes(self::INTERVAL_MINUTES);

            if ($start->lt($rangeEnd) && $end->gt($rangeStart)) {
                return true;
            }
        }

        return false;
    }
}
