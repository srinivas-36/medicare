<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Referral;
use App\Models\ReferralReport;
use Illuminate\Support\Facades\DB;

/**
 * Freezes a referral snapshot and the report ids that exist at send time.
 * Does not book the receiving doctor.
 */
class ReferralSender
{
    public function send(Appointment $appointment, Doctor $from, Doctor $to, string $reason): Referral
    {
        return DB::transaction(function () use ($appointment, $from, $to, $reason) {
            $appointment->load([
                'patient.patient_profile',
                'reports',
                'consultation',
            ]);
            $from->loadMissing('user');

            $consultation = $appointment->consultation;
            $profile = $appointment->patient?->patient_profile;
            $ready = $consultation?->ai_status === 'ready';
            $reportIds = $appointment->reports->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

            $referral = Referral::query()->create([
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'from_doctor_id' => $from->id,
                'to_doctor_id' => $to->id,
                'reason' => $reason,
                'status' => 'sent',
                'summary_snapshot' => [
                    'patient' => [
                        'name' => $appointment->patient?->name,
                        'age' => $profile?->age,
                        'blood_type' => $profile?->blood_type,
                        'health_notes' => $profile?->health_notes,
                    ],
                    'referring_doctor' => [
                        'name' => $from->user?->name,
                        'specialization' => $from->specialization,
                    ],
                    'visit' => [
                        'date' => $appointment->appointment_date?->toDateString(),
                        'reason' => $appointment->reason,
                        'notes' => $consultation?->clinical_notes,
                        'diagnosis' => $consultation?->diagnosis,
                        'plan' => $consultation?->plan,
                    ],
                    'ai_summary' => $ready ? $consultation->ai_summary : null,
                    'referral_brief' => $ready ? data_get($consultation->ai_analysis, 'referral_brief') : null,
                    'report_ids' => $reportIds,
                ],
            ]);

            foreach ($reportIds as $reportId) {
                ReferralReport::query()->create([
                    'referral_id' => $referral->id,
                    'appointment_report_id' => $reportId,
                ]);
            }

            return $referral;
        });
    }
}
