<?php

namespace App\Support;

use App\Models\Doctor;
use App\Services\AppointmentBooking;
use Illuminate\Pagination\LengthAwarePaginator;
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
            ->with(['user', 'timeSlots', 'qualifications', 'experiences'])
            ->where('approval_status', 'approved')
            ->whereHas('user', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('specialization')
            ->orderBy('id')
            ->get()
            ->map(function (Doctor $doctor) use ($booking) {
                $current = $doctor->experiences->firstWhere('is_current', true) ?? $doctor->experiences->first();

                return [
                    'id' => $doctor->id,
                    'name' => $doctor->user?->name ?? 'Doctor',
                    'specialization' => $doctor->specialization,
                    'qualifications' => $doctor->qualifications->pluck('degree')->filter()->take(3)->implode(' · '),
                    'experience' => $current?->designation
                        ? trim($current->designation.($current->hospital_name ? ' · '.$current->hospital_name : ''))
                        : '',
                    'fee' => number_format((float) $doctor->ticket_price, 2, '.', ''),
                    'photo' => $doctor->user?->photo,
                    'open' => $booking->calendar($doctor),
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
            ->pluck('specialization')
            ->values();
    }

    /**
     * The six public care areas. These cards stay on the site even when no
     * approved doctor has entered that specialty yet.
     *
     * @return Collection<int, array{name: string, photo: string, query: string, text: string}>
     */
    public static function services(): Collection
    {
        return collect([
            [
                'name' => 'Cancer Care',
                'photo' => 'images/cancer-care.jpg',
                'query' => 'cancer',
                'text' => "World-class cancer care with compassion. From early detection to advanced treatment, we're with you every step of the journey.",
            ],
            [
                'name' => 'Labor & Delivery',
                'photo' => 'images/labor-and-delivery.jpeg',
                'query' => 'labor',
                'text' => 'Personalized labor and delivery care, bringing new life into the world with confidence.',
            ],
            [
                'name' => 'Heart & Vascular',
                'photo' => 'images/Heart-andVascular.jpg',
                'query' => 'cardio',
                'text' => "Expert heart and vascular care for every patient. From diagnosis to advanced treatment, we're here for your heart health.",
            ],
            [
                'name' => 'Neurology',
                'photo' => 'images/Neurology.jpg',
                'query' => 'neuro',
                'text' => "Specialized neurology care for the brain and nervous system. From evaluation to treatment, we're with you at every step.",
            ],
            [
                'name' => 'Mental Health',
                'photo' => 'images/mental-health.jpg',
                'query' => 'mental',
                'text' => 'Compassionate mental health care, with support from the first conversation through ongoing treatment.',
            ],
            [
                'name' => 'Burn Care',
                'photo' => 'images/burn.webp',
                'query' => 'burn',
                'text' => 'Focused burn care from the first assessment through recovery, planned around healing and comfort.',
            ],
        ]);
    }

    public static function paginatedServices(int $perPage = 6): LengthAwarePaginator
    {
        $services = self::services();
        $lastPage = max(1, (int) ceil($services->count() / $perPage));
        $page = min(LengthAwarePaginator::resolveCurrentPage(), $lastPage);

        return (new LengthAwarePaginator(
            $services->forPage($page, $perPage)->values(),
            $services->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
            ],
        ))->withQueryString();
    }
}
