<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentReport;
use App\Models\Consultation;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Text-only visit draft. File bytes, storage paths, emails, phones,
 * and payment ids are left out of the request.
 */
class ClinicalAnalysis
{
    public function __construct(private PatientHistory $history) {}

    /**
     * @return array{summary: string, analysis: array{history_relevant_to_this_doctor: string, report_inventory: string, points_for_the_doctor: string, referral_brief: string}}
     */
    public function analyze(Consultation $consultation): array
    {
        $response = Http::retry(2, 100)
            ->withToken((string) config('services.ai.key'))
            ->acceptJson()
            ->timeout(30)
            ->post($this->endpoint(), [
                'model' => config('services.ai.model'),
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->instructions()],
                    ['role' => 'user', 'content' => $this->payload($consultation)],
                ],
            ]);

        if (! $response->successful()) {
            $response->throw();
        }

        $content = $response->json('choices.0.message.content');

        return $this->parse(is_string($content) ? $content : json_encode($content));
    }

    public function payload(Consultation $consultation): string
    {
        $consultation->loadMissing([
            'appointment.patient.patient_profile',
            'appointment.reports',
        ]);

        $appointment = $consultation->appointment;
        $profile = $appointment?->patient?->patient_profile;
        $health = $profile?->health_notes;

        $prior = $appointment
            ? $this->history->priorCompleted((int) $consultation->patient_id, (int) $consultation->doctor_id, $appointment)
            : collect();

        $priorLines = $prior->map(function (Appointment $visit) {
            return implode(' | ', [
                $visit->appointment_date?->toDateString() ?? '',
                'Reason: '.($visit->reason ?: 'none'),
                'Diagnosis: '.($visit->consultation?->diagnosis ?: 'none'),
                'Plan: '.($visit->consultation?->plan ?: 'none'),
            ]);
        })->implode("\n");

        $priorReports = $prior->flatMap(function (Appointment $visit) {
            return $visit->reports->map(fn (AppointmentReport $report) => $report->group_name.': '.$report->original_name);
        })->implode("\n");

        return implode("\n\n", [
            'Age: '.($profile?->age ?? 'not recorded'),
            'Blood type: '.($profile?->blood_type ?: 'not recorded'),
            'Health notes: '.(is_array($health) && $health !== [] ? json_encode($health, JSON_UNESCAPED_UNICODE) : 'none'),
            'This visit date: '.($appointment?->appointment_date?->toDateString() ?? ''),
            'Visit type: '.($appointment?->visit_type ?: ''),
            'Reason: '.($appointment?->reason ?: 'none'),
            'Clinical notes: '.($consultation->clinical_notes ?: 'none'),
            'Diagnosis: '.($consultation->diagnosis ?: 'none'),
            'Plan: '.($consultation->plan ?: 'none'),
            "Prior completed visits with this doctor:\n".($priorLines !== '' ? $priorLines : 'none'),
            "Reports on this visit:\n".$this->reportLines($appointment),
            "Reports on earlier visits with this doctor:\n".($priorReports !== '' ? $priorReports : 'none'),
        ]);
    }

    private function reportLines(?Appointment $appointment): string
    {
        if (! $appointment || $appointment->reports->isEmpty()) {
            return 'none';
        }

        return $appointment->reports
            ->map(fn (AppointmentReport $report) => $report->group_name.': '.$report->original_name)
            ->implode("\n");
    }

    private function instructions(): string
    {
        return 'Use only the supplied facts. If a report is only a filename, say the file was attached and do not invent results. Write referral_brief so another doctor can read it without the chart. Reply with JSON containing the keys summary, history_relevant_to_this_doctor, report_inventory, points_for_the_doctor, and referral_brief.';
    }

    private function endpoint(): string
    {
        return rtrim((string) config('services.ai.base_url'), '/').'/chat/completions';
    }

    /**
     * @return array{summary: string, analysis: array{history_relevant_to_this_doctor: string, report_inventory: string, points_for_the_doctor: string, referral_brief: string}}
     */
    private function parse(string $content): array
    {
        $content = trim($content);

        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $content) ?? $content;
            $content = trim($content);
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('The analysis response was not JSON.');
        }

        return [
            'summary' => $this->text($decoded['summary'] ?? ''),
            'analysis' => [
                'history_relevant_to_this_doctor' => $this->text($decoded['history_relevant_to_this_doctor'] ?? ''),
                'report_inventory' => $this->text($decoded['report_inventory'] ?? ''),
                'points_for_the_doctor' => $this->text($decoded['points_for_the_doctor'] ?? ''),
                'referral_brief' => $this->text($decoded['referral_brief'] ?? ''),
            ],
        ];
    }

    private function text(mixed $value): string
    {
        if (is_array($value)) {
            return trim(implode("\n", array_map(function ($item) {
                return is_scalar($item) ? (string) $item : (string) json_encode($item);
            }, $value)));
        }

        return trim((string) $value);
    }
}
