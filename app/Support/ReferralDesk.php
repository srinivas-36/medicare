<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\ReferralSender;

/**
 * The specialist picker used on a visit. The patient chart opens the same desk.
 */
class ReferralDesk
{
    /**
     * @return array{
     *     referralDoctors: list<array<string, mixed>>,
     *     referralPreview: bool,
     *     referralSymptoms: list<string>,
     *     referralSpecialties: list<string>,
     *     referralDraft: string,
     *     referralSummary: array<string, mixed>,
     *     sentReferrals: list<array<string, mixed>>
     * }
     */
    public static function for(Doctor $doctor, Appointment $appointment): array
    {
        $appointment->loadMissing([
            'patient',
            'reports',
            'consultation',
            'referrals.toDoctor.user',
        ]);

        $directory = self::doctors($doctor);
        $preview = $directory === [];
        $clinical = collect([
            $appointment->reason,
            $appointment->consultation?->diagnosis,
            $appointment->consultation?->clinical_notes,
        ])->filter(fn ($value) => filled($value))->implode("\n");
        $suggested = ReferralSuggestions::prepare(
            $preview ? MockReferralDirectory::doctors() : $directory,
            $clinical,
        );
        $draft = $suggested['symptoms'] === []
            ? ''
            : 'Please assess '.($appointment->patient?->name ?: 'this patient').' for '.implode(', ', $suggested['symptoms']).'.';

        return [
            'referralDoctors' => $suggested['doctors'],
            'referralPreview' => $preview,
            'referralSymptoms' => $suggested['symptoms'],
            'referralSpecialties' => $suggested['specialties'],
            'referralDraft' => $draft,
            'referralSummary' => app(ReferralSender::class)->patientPacket($appointment),
            'sentReferrals' => $appointment->referrals
                ->sortByDesc('id')
                ->map(fn ($referral) => [
                    'id' => $referral->id,
                    'doctor' => $referral->toDoctor?->user?->name ?? 'Doctor',
                    'specialization' => $referral->toDoctor?->specialization ?? '',
                    'status' => $referral->statusLabel(),
                    'reason' => $referral->reason,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function doctors(Doctor $doctor): array
    {
        return Doctor::query()
            ->with(['user', 'qualifications', 'experiences'])
            ->where('approval_status', 'approved')
            ->whereKeyNot($doctor->id)
            ->whereHas('user', function ($query) {
                $query->where('role', 'doctor')->where('is_active', true);
            })
            ->orderBy('specialization')
            ->orderBy('id')
            ->get()
            ->map(function (Doctor $recipient): array {
                $current = $recipient->experiences->firstWhere('is_current', true) ?? $recipient->experiences->first();
                $name = $recipient->user?->name ?? 'Doctor';
                $degree = $recipient->qualifications->pluck('degree')->filter()->take(2)->implode(', ');
                $fee = (float) $recipient->ticket_price;

                return [
                    'id' => (string) $recipient->id,
                    'preview' => false,
                    'name' => $name,
                    'initials' => ReferralSuggestions::initials($name),
                    'specialization' => (string) ($recipient->specialization ?: 'Specialist'),
                    'degree' => $degree,
                    'clinic' => (string) ($current?->hospital_name ?? ''),
                    'experience' => $current?->designation
                        ? trim($current->designation.($current->hospital_name ? ' · '.$current->hospital_name : ''))
                        : '',
                    'bio' => trim((string) ($recipient->bio ?: $recipient->about ?: '')),
                    'rating' => (float) $recipient->average_rating,
                    'reviews' => (int) $recipient->total_rating,
                    'fee' => $fee > 0 ? '₹'.number_format($fee, 0) : '',
                    'photo' => AppointmentPage::photoUrl($recipient->user?->photo),
                    'focus' => [],
                ];
            })
            ->values()
            ->all();
    }
}
