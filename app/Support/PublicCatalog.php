<?php

namespace App\Support;

use App\Models\Doctor;
use App\Models\TimeSlot;
use App\Services\AppointmentBooking;
use Illuminate\Support\Collection;

/**
 * Public homepage data.
 * Only approved doctors with an active account are included.
 */
class PublicCatalog
{
    public static function featuredDoctors(int $limit = 4): Collection
    {
        return Doctor::query()
            ->with(['user', 'qualifications', 'timeSlots'])
            ->where('approval_status', 'approved')
            ->whereHas('user', function ($query) {
                $query->where('is_active', true);
            })
            ->latest()
            ->limit($limit)
            ->get();
    }

    public static function bookableDoctors(): Collection
    {
        return Doctor::query()
            ->with(['user', 'qualifications'])
            ->where('approval_status', 'approved')
            ->whereHas('user', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('specialization')
            ->orderBy('id')
            ->get();
    }

    /**
     * Approved doctors, with the windows the dashboard booking form can offer.
     *
     * @return list<array<string, mixed>>
     */
    public static function bookingChoices(): array
    {
        $booking = app(AppointmentBooking::class);

        return Doctor::query()
            ->with([
                'user',
                'timeSlots',
                'appointments' => function ($query) {
                    $query->where('status', '!=', 'cancelled')
                        ->whereDate('appointment_date', '>=', today()->toDateString())
                        ->whereDate('appointment_date', '<=', today()->addDays(AppointmentBooking::DAYS - 1)->toDateString());
                },
            ])
            ->where('approval_status', 'approved')
            ->whereHas('user', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('specialization')
            ->orderBy('id')
            ->get()
            ->map(function (Doctor $doctor) use ($booking) {
                return [
                    'id' => $doctor->id,
                    'name' => $doctor->user?->name ?? 'Doctor',
                    'specialization' => $doctor->specialization,
                    'fee' => number_format((float) $doctor->ticket_price, 2, '.', ''),
                    'slots' => $doctor->timeSlots
                        ->filter(fn (TimeSlot $slot) => (bool) $slot->is_available)
                        ->map(fn (TimeSlot $slot) => [
                            'day' => strtolower((string) $slot->day),
                            'startingTime' => substr(AppointmentBooking::normalizeTime((string) $slot->starting_time), 0, 5),
                            'endingTime' => substr(AppointmentBooking::normalizeTime((string) $slot->ending_time), 0, 5),
                        ])
                        ->values()
                        ->all(),
                    'blocked' => $booking->blockedWindows($doctor),
                ];
            })
            ->all();
    }

    public static function specialties(): Collection
    {
        return Doctor::query()
            ->where('approval_status', 'approved')
            ->whereHas('user', function ($query) {
                $query->where('is_active', true);
            })
            ->whereNotNull('specialization')
            ->where('specialization', '!=', '')
            ->orderBy('specialization')
            ->distinct()
            ->pluck('specialization');
    }
}
