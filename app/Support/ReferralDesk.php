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

        $clinical = collect([
            $appointment->reason,
            $appointment->consultation?->diagnosis,
            $appointment->consultation?->clinical_notes,
        ])->filter(fn ($value) => filled($value))->implode("\n");
        $suggested = ReferralSuggestions::prepare(MockReferralDirectory::doctors(), $clinical);
        $draft = $suggested['symptoms'] === []
            ? ''
            : 'Please assess '.($appointment->patient?->name ?: 'this patient').' for '.implode(', ', $suggested['symptoms']).'.';

        return [
            'referralDoctors' => $suggested['doctors'],
            'referralPreview' => true,
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
}
