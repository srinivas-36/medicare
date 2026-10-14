<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentReschedule;
use App\Models\ClinicSetting;
use App\Models\Doctor;
use App\Models\DoctorAvailabilityException;
use App\Models\TimeSlot;
use App\Models\User;
use App\Support\PatientRoster;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Shared appointment slots for public, patient, doctor, receptionist, and referral booking.
 * A bookable slot is one time range the doctor saved, after leave and existing visits.
 */
class AppointmentBooking
{
    public const INTERVAL_MINUTES = 30;

    public const DAYS = 14;

    public static function bookableUntil(): Carbon
    {
        $months = 6;

        try {
            if (Schema::hasTable('clinic_settings')) {
                $stored = ClinicSetting::query()->value('booking_horizon_months');
                if ($stored) {
                    $months = (int) $stored;
                }
            }
        } catch (\Throwable) {
            $months = 6;
        }

        return self::clinicNow()->startOfDay()->addMonths(max(1, min(24, $months)));
    }

    public static function clinicNow(): Carbon
    {
        $timezone = (string) config('app.timezone', 'UTC');

        try {
            if (Schema::hasTable('clinic_settings')) {
                $stored = ClinicSetting::query()->value('timezone');
                if (is_string($stored) && $stored !== '') {
                    $timezone = $stored;
                }
            }
        } catch (\Throwable) {
            // Keep the application timezone when settings are unavailable.
        }

        return Carbon::now($timezone);
    }

    /**
     * @return array<string, list<string>>
     */
    public function slotsForNextDays(Doctor $doctor, ?int $days = null): array
    {
        $slots = [];
        $start = self::clinicNow()->startOfDay();
        $end = $days === null
            ? self::bookableUntil()->copy()->startOfDay()
            : $start->copy()->addDays(max(1, $days) - 1);

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $open = $this->openTimes($doctor, $day);
            if ($open !== []) {
                $slots[$day->toDateString()] = $open;
            }
        }

        return $slots;
    }

    /**
     * @return array<string, list<array{start: string, end: string}>>
     */
    public function calendar(Doctor $doctor, ?Carbon $from = null, ?Carbon $until = null, ?int $ignoreAppointmentId = null): array
    {
        $start = ($from ?? self::clinicNow())->copy()->startOfDay();
        $end = ($until ?? self::bookableUntil())->copy()->startOfDay();

        if ($end->lt($start) || ! $this->doctorBookable($doctor)) {
            return [];
        }

        $doctor->loadMissing(['user', 'timeSlots']);

        $appointments = $this->appointmentsBetween($doctor, $start, $end)
            ->when($ignoreAppointmentId, fn (Collection $rows) => $rows->reject(
                fn (Appointment $appointment) => (int) $appointment->id === $ignoreAppointmentId
            ))
            ->groupBy(fn (Appointment $appointment) => $appointment->appointment_date->toDateString());

        $exceptions = $doctor->availabilityExceptions()
            ->whereDate('ends_on', '>=', $start->toDateString())
            ->whereDate('starts_on', '<=', $end->toDateString())
            ->get();

        $open = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $key = $day->toDateString();
            $slices = $this->slicesFor($doctor, $day, $appointments->get($key, collect()), $exceptions);

            if ($slices !== []) {
                $open[$key] = $slices;
            }
        }

        return $open;
    }

    /**
     * @return list<string> Times as H:i
     */
    public function openTimes(Doctor $doctor, Carbon $date, bool $fresh = false): array
    {
        return array_map(
            fn (array $slot) => $slot['start'],
            $this->openSlots($doctor, $date, $fresh),
        );
    }

    /**
     * @return list<array{start: string, end: string}>
     */
    public function openSlots(Doctor $doctor, Carbon $date, bool $fresh = false): array
    {
        if (! $this->dateAllowed($date) || ! $this->doctorBookable($doctor)) {
            return [];
        }

        $exceptions = $doctor->availabilityExceptions()
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->whereDate('ends_on', '>=', $date->toDateString())
            ->get();

        $day = $this->clinicDay($date);

        return $this->slicesFor(
            $doctor,
            $day,
            $this->appointmentsOn($doctor, $day->toDateString(), $fresh),
            $exceptions,
        );
    }

    public function create(
        User $patient,
        Doctor $doctor,
        string $date,
        string $time,
        ?string $reason,
        string $visitType,
        string $status,
        string $source = 'doctor',
        ?User $bookedBy = null,
    ): Appointment {
        if ($patient->role !== 'patient') {
            throw ValidationException::withMessages([
                'patient_id' => 'Choose a patient account.',
            ]);
        }

        return DB::transaction(function () use ($patient, $doctor, $date, $time, $reason, $visitType, $status, $source, $bookedBy) {
            $locked = $this->lockDoctor($doctor);
            $this->assertOpen($locked, $date, $time);
            $ending = $this->sliceEnd($locked, $date, $time);

            return $this->insert($locked, $patient, $date, $time, $ending, $reason, $visitType, $status, $source, null, $bookedBy);
        });
    }

    /**
     * @return array<string, string|list<string>>
     */
    public function blockedWindows(Doctor $doctor, ?Carbon $until = null): array
    {
        $calendar = $this->calendar($doctor, self::clinicNow()->startOfDay(), $until ?? self::bookableUntil());
        $blocked = [];

        foreach ($calendar as $date => $slots) {
            $blocked[$date] = [];
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
        ?User $bookedBy = null,
    ): Appointment {
        if ($patient->role !== 'patient') {
            throw ValidationException::withMessages([
                'appointment_date' => 'Choose a patient account.',
            ]);
        }

        return DB::transaction(function () use ($patient, $doctor, $date, $startingTime, $endingTime, $reason, $reports, $bookedBy) {
            $locked = $this->lockDoctor($doctor);
            $this->assertWindow($locked, $date, $startingTime, $endingTime);

            $appointment = $this->insert(
                $locked,
                $patient,
                $date,
                $startingTime,
                $endingTime,
                $reason,
                'consultation',
                'confirmed',
                'public',
                $locked->ticket_price,
                $bookedBy ?? $patient,
            );

            foreach ($reports as $report) {
                $appointment->reports()->create($report);
            }

            return $appointment;
        });
    }

    public function confirmWindow(
        User $patient,
        Doctor $doctor,
        string $date,
        string $startingTime,
        string $endingTime,
        ?string $reason,
        string $visitType,
        ?User $bookedBy = null,
    ): Appointment {
        if ($patient->role !== 'patient') {
            throw ValidationException::withMessages([
                'patient_id' => 'Choose a patient account.',
            ]);
        }

        return DB::transaction(function () use ($patient, $doctor, $date, $startingTime, $endingTime, $reason, $visitType, $bookedBy) {
            $locked = $this->lockDoctor($doctor);
            $this->assertWindow($locked, $date, $startingTime, $endingTime);

            return $this->insert(
                $locked,
                $patient,
                $date,
                $startingTime,
                $endingTime,
                $reason,
                $visitType,
                'confirmed',
                'receptionist',
                null,
                $bookedBy,
            );
        });
    }

    public function reschedule(
        Appointment $appointment,
        User $actor,
        string $date,
        string $time,
        ?string $reason = null,
    ): Appointment {
        if (in_array($appointment->status, ['completed', 'cancelled', 'no_show'], true)) {
            throw ValidationException::withMessages([
                'date' => 'This appointment can no longer be moved.',
            ]);
        }

        return DB::transaction(function () use ($appointment, $actor, $date, $time, $reason) {
            $lockedAppointment = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $doctor = $this->lockDoctor($lockedAppointment->doctor);
            $this->assertOpen($doctor, $date, $time, $lockedAppointment->id);
            $ending = $this->sliceEnd($doctor, $date, $time);

            AppointmentReschedule::query()->create([
                'appointment_id' => $lockedAppointment->id,
                'actor_id' => $actor->id,
                'from_date' => $lockedAppointment->appointment_date->toDateString(),
                'from_time' => self::normalizeTime((string) $lockedAppointment->appointment_time),
                'from_ending' => filled($lockedAppointment->ending_time) ? self::normalizeTime((string) $lockedAppointment->ending_time) : null,
                'to_date' => Carbon::parse($date)->toDateString(),
                'to_time' => self::normalizeTime($time),
                'to_ending' => self::normalizeTime($ending),
                'reason' => $reason,
            ]);

            try {
                $lockedAppointment->forceFill([
                    'appointment_date' => Carbon::parse($date)->toDateString(),
                    'appointment_time' => self::normalizeTime($time),
                    'ending_time' => self::normalizeTime($ending),
                ])->save();
            } catch (QueryException $exception) {
                if ($this->slotJustTaken($exception)) {
                    throw ValidationException::withMessages([
                        'time' => 'That time was just booked. Choose another slot.',
                    ]);
                }

                throw $exception;
            }

            $fresh = $lockedAppointment->fresh();

            DB::afterCommit(function () use ($fresh) {
                app(ClinicNotifier::class)->appointmentChanged(
                    $fresh,
                    'Appointment rescheduled',
                    'The visit is now '.$fresh->appointment_date?->format('M j, Y').' at '.$fresh->appointment_time.'.',
                );
            });

            return $fresh;
        });
    }

    public function assertWindow(Doctor $doctor, string $date, string $startingTime, string $endingTime): void
    {
        $this->assertOpen($doctor, $date, $startingTime);

        $start = substr(self::normalizeTime($startingTime), 0, 5);
        $end = substr(self::normalizeTime($endingTime), 0, 5);
        $matches = false;

        foreach ($this->openSlots($doctor, Carbon::parse($date)->startOfDay(), true) as $slot) {
            if ($slot['start'] === $start && $slot['end'] === $end) {
                $matches = true;
                break;
            }
        }

        if (! $matches) {
            throw ValidationException::withMessages([
                'starting_time' => 'That time is not available.',
            ]);
        }
    }

    public function cents(string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public function assertOpen(Doctor $doctor, string $date, string $time, ?int $ignoreAppointmentId = null): void
    {
        try {
            $day = $this->clinicDay($date);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'date' => 'Choose a valid date.',
            ]);
        }

        if (! $this->dateAllowed($day)) {
            throw ValidationException::withMessages([
                'date' => 'Choose a date from today through '.$this->horizonLabel().'.',
                'appointment_date' => 'Choose a date from today through '.$this->horizonLabel().'.',
            ]);
        }

        if (! $this->doctorBookable($doctor)) {
            throw ValidationException::withMessages([
                'date' => 'This doctor is not available for booking.',
                'appointment_date' => 'This doctor is not available for booking.',
            ]);
        }

        $available = $ignoreAppointmentId === null
            ? $this->openTimes($doctor, $day, true)
            : array_map(
                fn (array $slot) => $slot['start'],
                $this->slicesFor(
                    $doctor,
                    $day,
                    $this->appointmentsOn($doctor, $day->toDateString(), true)
                        ->reject(fn (Appointment $appointment) => (int) $appointment->id === $ignoreAppointmentId)
                        ->values(),
                    $doctor->availabilityExceptions()
                        ->whereDate('starts_on', '<=', $day->toDateString())
                        ->whereDate('ends_on', '>=', $day->toDateString())
                        ->get(),
                ),
            );

        $needle = substr(self::normalizeTime($time), 0, 5);

        if (! in_array($needle, $available, true)) {
            throw ValidationException::withMessages([
                'time' => 'That time is not available.',
                'starting_time' => 'That time is not available.',
            ]);
        }
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function conflictingAppointments(Doctor $doctor, Carbon $start, Carbon $end, ?string $fromTime = null, ?string $toTime = null): Collection
    {
        return $this->appointmentsBetween($doctor, $start, $end)
            ->filter(function (Appointment $appointment) use ($fromTime, $toTime) {
                if ($fromTime === null || $toTime === null) {
                    return true;
                }

                $date = $appointment->appointment_date->toDateString();
                $appointmentStart = $this->atClinic($date, (string) $appointment->appointment_time);
                $appointmentEnd = $this->occupiedEnd($appointment, $date, 0);

                $blockStart = $this->atClinic($date, $fromTime);
                $blockEnd = $this->atClinic($date, $toTime);

                return $appointmentStart->lt($blockEnd) && $appointmentEnd->gt($blockStart);
            })
            ->values();
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

    private function lockDoctor(Doctor $doctor): Doctor
    {
        $locked = Doctor::query()->whereKey($doctor->id)->lockForUpdate()->firstOrFail();
        $locked->setRelation('user', $locked->user()->first());

        return $locked;
    }

    private function insert(
        Doctor $doctor,
        User $patient,
        string $date,
        string $startingTime,
        string $endingTime,
        ?string $reason,
        string $visitType,
        string $status,
        string $source,
        mixed $ticketPrice = null,
        ?User $bookedBy = null,
    ): Appointment {
        $actor = $bookedBy;

        if (! $actor && in_array($source, ['public', 'patient'], true)) {
            $actor = $patient;
        }

        if (! $actor && in_array($source, ['doctor', 'referral'], true)) {
            $actor = $doctor->user;
        }

        try {
            $appointment = Appointment::query()->create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'appointment_date' => Carbon::parse($date)->toDateString(),
                'appointment_time' => self::normalizeTime($startingTime),
                'ending_time' => self::normalizeTime($endingTime),
                'status' => $status,
                'reason' => $reason,
                'visit_type' => $visitType,
                'source' => $source,
                'booked_by' => $actor?->id,
                'ticket_price' => $ticketPrice,
                'payment_status' => null,
            ]);
        } catch (QueryException $exception) {
            if ($this->slotJustTaken($exception)) {
                throw ValidationException::withMessages([
                    'starting_time' => 'That time was just booked. Choose another slot.',
                    'time' => 'That time was just booked. Choose another slot.',
                ]);
            }

            throw $exception;
        }

        $origin = match ($source) {
            'public', 'patient' => 'booking',
            'receptionist' => 'receptionist',
            'referral' => 'referral',
            default => 'doctor',
        };

        PatientRoster::remember($doctor, $patient, $origin, $actor?->id);

        DB::afterCommit(function () use ($appointment) {
            app(ClinicNotifier::class)->appointmentBooked($appointment);
        });

        return $appointment;
    }

    private function sliceEnd(Doctor $doctor, string $date, string $time): string
    {
        $needle = substr(self::normalizeTime($time), 0, 5);
        $day = $this->clinicDay($date);
        $exceptions = $doctor->availabilityExceptions()
            ->whereDate('starts_on', '<=', $day->toDateString())
            ->whereDate('ends_on', '>=', $day->toDateString())
            ->get();

        foreach ($this->windowsForDate($doctor, $day, $exceptions) as [$start, $end]) {
            if ($start->format('H:i') === $needle) {
                return $end->format('H:i');
            }
        }

        return $this->atClinic($day->toDateString(), $needle)
            ->addMinutes($this->visitMinutes($doctor))
            ->format('H:i');
    }

    private function dateAllowed(Carbon $day): bool
    {
        $date = $this->clinicDay($day)->toDateString();

        return $date >= self::clinicNow()->toDateString()
            && $date <= self::bookableUntil()->toDateString();
    }

    private function clinicDay(Carbon|string $date): Carbon
    {
        $value = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        return Carbon::parse($value, $this->timezoneName())->startOfDay();
    }

    private function atClinic(string $date, string $time): Carbon
    {
        return Carbon::parse($date.' '.self::normalizeTime($time), $this->timezoneName());
    }

    private function doctorBookable(Doctor $doctor): bool
    {
        $user = $doctor->relationLoaded('user') ? $doctor->user : $doctor->user()->first();

        return $doctor->approval_status === 'approved' && $user && $user->is_active;
    }

    private function horizonLabel(): string
    {
        return self::bookableUntil()->format('M j, Y');
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     * @param  Collection<int, DoctorAvailabilityException>  $exceptions
     * @return list<array{start: string, end: string}>
     */
    private function slicesFor(Doctor $doctor, Carbon $day, Collection $appointments, Collection $exceptions): array
    {
        $windows = $this->windowsForDate($doctor, $day, $exceptions);

        if ($windows === []) {
            return [];
        }

        $length = $this->visitMinutes($doctor);
        $buffer = $this->bufferMinutes($doctor);
        $earliest = $this->earliestBookable();
        $times = [];

        foreach ($windows as [$windowStart, $windowEnd]) {
            if ($windowEnd->lte($windowStart) || $windowStart->lte($earliest)) {
                continue;
            }

            if ($this->rangeTaken($appointments, $day->toDateString(), $windowStart, $windowEnd, $buffer, $length)) {
                continue;
            }

            $times[$windowStart->format('H:i').'|'.$windowEnd->format('H:i')] = [
                'start' => $windowStart->format('H:i'),
                'end' => $windowEnd->format('H:i'),
            ];
        }

        ksort($times);

        return array_values($times);
    }

    /**
     * @param  Collection<int, DoctorAvailabilityException>  $exceptions
     * @return list<array{0: Carbon, 1: Carbon}>
     */
    private function windowsForDate(Doctor $doctor, Carbon $day, Collection $exceptions): array
    {
        $date = $day->toDateString();
        $applicable = $exceptions->filter(function (DoctorAvailabilityException $exception) use ($date) {
            return $exception->starts_on->toDateString() <= $date
                && $exception->ends_on->toDateString() >= $date;
        })->values();

        if ($applicable->contains(fn (DoctorAvailabilityException $exception) => $exception->kind === 'unavailable' && blank($exception->starts_at))) {
            return [];
        }

        $override = $applicable->first(fn (DoctorAvailabilityException $exception) => $exception->kind === 'override' && filled($exception->starts_at) && filled($exception->ends_at));

        if ($override) {
            $windows = [[
                $this->atClinic($date, (string) $override->starts_at),
                $this->atClinic($date, (string) $override->ends_at),
            ]];
        } else {
            $weekday = strtolower($day->copy()->timezone($this->timezoneName())->format('l'));
            $windows = $this->windows($doctor, $weekday, false)
                ->map(fn (TimeSlot $slot) => [
                    $this->atClinic($date, (string) $slot->starting_time),
                    $this->atClinic($date, (string) $slot->ending_time),
                ])
                ->all();
        }

        foreach ($applicable as $exception) {
            if ($exception->kind === 'extra' && filled($exception->starts_at) && filled($exception->ends_at)) {
                $windows[] = [
                    $this->atClinic($date, (string) $exception->starts_at),
                    $this->atClinic($date, (string) $exception->ends_at),
                ];
            }

            if ($exception->kind === 'unavailable' && filled($exception->starts_at) && filled($exception->ends_at)) {
                $blockStart = $this->atClinic($date, (string) $exception->starts_at);
                $blockEnd = $this->atClinic($date, (string) $exception->ends_at);
                $windows = array_values(array_filter($windows, function (array $window) use ($blockStart, $blockEnd) {
                    [$start, $end] = $window;

                    return $blockEnd->lte($start) || $blockStart->gte($end);
                }));
            }
        }

        return array_values(array_filter($windows, fn (array $window) => $window[1]->gt($window[0])));
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
                return $appointment->status !== 'cancelled'
                    && $appointment->appointment_date->toDateString() === $date;
            })
            ->values();
    }

    /**
     * @return Collection<int, Appointment>
     */
    private function appointmentsBetween(Doctor $doctor, Carbon $start, Carbon $end): Collection
    {
        return $doctor->appointments()
            ->where('status', '!=', 'cancelled')
            ->whereDate('appointment_date', '>=', $start->toDateString())
            ->whereDate('appointment_date', '<=', $end->toDateString())
            ->get();
    }

    private ?string $timezoneName = null;

    private ?int $notice = null;

    private ?Carbon $earliest = null;

    private function timezoneName(): string
    {
        if ($this->timezoneName === null) {
            $this->timezoneName = self::clinicNow()->timezoneName;
        }

        return $this->timezoneName;
    }

    private function earliestBookable(): Carbon
    {
        if ($this->earliest === null) {
            $this->earliest = self::clinicNow()->copy()->addMinutes($this->noticeMinutes());
        }

        return $this->earliest->copy();
    }

    /**
     * Visit-length openings inside saved hours. Used by the front desk.
     *
     * @return array<string, list<array{start: string, end: string}>>
     */
    public function visitCalendar(Doctor $doctor, ?Carbon $from = null, ?Carbon $until = null): array
    {
        $start = ($from ?? self::clinicNow())->copy()->startOfDay();
        $end = ($until ?? self::bookableUntil())->copy()->startOfDay();

        if ($end->lt($start) || ! $this->doctorBookable($doctor)) {
            return [];
        }

        $doctor->loadMissing(['user', 'timeSlots']);
        $appointments = $this->appointmentsBetween($doctor, $start, $end)
            ->groupBy(fn (Appointment $appointment) => $appointment->appointment_date->toDateString());
        $exceptions = $doctor->availabilityExceptions()
            ->whereDate('ends_on', '>=', $start->toDateString())
            ->whereDate('starts_on', '<=', $end->toDateString())
            ->get();
        $earliest = $this->earliestBookable();
        $open = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $key = $day->toDateString();
            $slices = $this->visitSlices($doctor, $day, $appointments->get($key, collect()), $exceptions, $earliest);

            if ($slices !== []) {
                $open[$key] = $slices;
            }
        }

        return $open;
    }

    /**
     * @return list<array{start: string, end: string}>
     */
    public function visitSlots(Doctor $doctor, Carbon $date, bool $fresh = false): array
    {
        if (! $this->dateAllowed($date) || ! $this->doctorBookable($doctor)) {
            return [];
        }

        $day = $this->clinicDay($date);
        $exceptions = $doctor->availabilityExceptions()
            ->whereDate('starts_on', '<=', $day->toDateString())
            ->whereDate('ends_on', '>=', $day->toDateString())
            ->get();

        return $this->visitSlices(
            $doctor,
            $day,
            $this->appointmentsOn($doctor, $day->toDateString(), $fresh),
            $exceptions,
            $this->earliestBookable(),
        );
    }

    public function confirmVisit(
        User $patient,
        Doctor $doctor,
        string $date,
        string $startingTime,
        string $endingTime,
        ?string $reason,
        string $visitType,
    ): Appointment {
        if ($patient->role !== 'patient') {
            throw ValidationException::withMessages([
                'patient_id' => 'Choose a patient account.',
            ]);
        }

        return DB::transaction(function () use ($patient, $doctor, $date, $startingTime, $endingTime, $reason, $visitType) {
            $locked = $this->lockDoctor($doctor);
            $this->assertVisit($locked, $date, $startingTime, $endingTime);

            return $this->insert(
                $locked,
                $patient,
                $date,
                $startingTime,
                $endingTime,
                $reason,
                $visitType,
                'confirmed',
                'receptionist',
            );
        });
    }

    public function assertVisit(Doctor $doctor, string $date, string $startingTime, string $endingTime): void
    {
        try {
            $day = $this->clinicDay($date);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'date' => 'Choose a valid date.',
            ]);
        }

        if (! $this->dateAllowed($day)) {
            throw ValidationException::withMessages([
                'date' => 'Choose a date from today through '.$this->horizonLabel().'.',
            ]);
        }

        if (! $this->doctorBookable($doctor)) {
            throw ValidationException::withMessages([
                'date' => 'This doctor is not available for booking.',
            ]);
        }

        $start = substr(self::normalizeTime($startingTime), 0, 5);
        $end = substr(self::normalizeTime($endingTime), 0, 5);
        $matches = false;

        foreach ($this->visitSlots($doctor, $day, true) as $slot) {
            if ($slot['start'] === $start && $slot['end'] === $end) {
                $matches = true;
                break;
            }
        }

        if (! $matches) {
            throw ValidationException::withMessages([
                'starting_time' => 'That time is not available.',
            ]);
        }
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     * @param  Collection<int, DoctorAvailabilityException>  $exceptions
     * @return list<array{start: string, end: string}>
     */
    private function visitSlices(Doctor $doctor, Carbon $day, Collection $appointments, Collection $exceptions, Carbon $earliest): array
    {
        $windows = $this->windowsForDate($doctor, $day, $exceptions);

        if ($windows === []) {
            return [];
        }

        $length = $this->visitMinutes($doctor);
        $buffer = $this->bufferMinutes($doctor);
        $step = max(1, $length + $buffer);
        $times = [];

        foreach ($windows as [$windowStart, $windowEnd]) {
            if ($windowEnd->lte($windowStart)) {
                continue;
            }

            for ($cursor = $windowStart->copy(); $cursor->copy()->addMinutes($length)->lte($windowEnd); $cursor->addMinutes($step)) {
                $sliceEnd = $cursor->copy()->addMinutes($length);

                if ($cursor->lte($earliest)) {
                    continue;
                }

                if ($this->rangeTaken($appointments, $day->toDateString(), $cursor, $sliceEnd, $buffer, $length)) {
                    continue;
                }

                $times[$cursor->format('H:i')] = [
                    'start' => $cursor->format('H:i'),
                    'end' => $sliceEnd->format('H:i'),
                ];
            }
        }

        ksort($times);

        return array_values($times);
    }

    private function visitMinutes(Doctor $doctor): int
    {
        $minutes = (int) ($doctor->appointment_duration ?? self::INTERVAL_MINUTES);

        return $minutes > 0 ? $minutes : self::INTERVAL_MINUTES;
    }

    private function bufferMinutes(Doctor $doctor): int
    {
        return max(0, (int) ($doctor->appointment_buffer ?? 0));
    }

    private function noticeMinutes(): int
    {
        if ($this->notice !== null) {
            return $this->notice;
        }

        try {
            $this->notice = max(0, (int) ClinicSetting::query()->value('minimum_notice_minutes'));
        } catch (\Throwable) {
            $this->notice = 0;
        }

        return $this->notice;
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     */
    private function rangeTaken(Collection $appointments, string $date, Carbon $rangeStart, Carbon $rangeEnd, int $buffer, int $fallbackMinutes): bool
    {
        foreach ($appointments as $appointment) {
            $start = $this->atClinic($date, (string) $appointment->appointment_time);
            $end = $this->occupiedEnd($appointment, $date, $fallbackMinutes)->addMinutes($buffer);

            if ($start->lt($rangeEnd) && $end->gt($rangeStart)) {
                return true;
            }
        }

        return false;
    }

    private function slotJustTaken(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $duplicate = str_contains($message, 'duplicate') || str_contains($message, 'unique constraint');

        return $duplicate && str_contains($message, 'active_slot');
    }

    private function occupiedEnd(Appointment $appointment, string $date, int $fallbackMinutes): Carbon
    {
        $start = $this->atClinic($date, (string) $appointment->appointment_time);

        if (filled($appointment->ending_time)) {
            return $this->atClinic($date, (string) $appointment->ending_time);
        }

        return $start->copy()->addMinutes($fallbackMinutes > 0 ? $fallbackMinutes : self::INTERVAL_MINUTES);
    }
}
