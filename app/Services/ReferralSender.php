<?php

namespace App\Services;

use App\Models\AiAnalysis;
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
    /**
     * @param  list<int>|null  $reportIds
     */
    public function send(
        Appointment $appointment,
        Doctor $from,
        Doctor $to,
        string $reason,
        ?array $reportIds = null,
        string $urgency = 'routine',
        ?string $clinicalQuestion = null,
    ): Referral {
        return DB::transaction(function () use ($appointment, $from, $to, $reason, $reportIds, $urgency, $clinicalQuestion) {
            $appointment->load([
                'patient.patient_profile',
                'reports',
                'consultation',
            ]);
            $from->loadMissing('user');

            $consultation = $appointment->consultation;
            $profile = $appointment->patient?->patient_profile;
            $availableIds = $appointment->reports->pluck('id')->map(fn ($id) => (int) $id);
            $reportIds = $reportIds === null
                ? $availableIds->values()->all()
                : $availableIds->intersect(array_map('intval', $reportIds))->values()->all();
            $packet = $this->patientPacket($appointment, $reportIds);

            $referral = Referral::query()->create([
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'from_doctor_id' => $from->id,
                'to_doctor_id' => $to->id,
                'reason' => $reason,
                'urgency' => $urgency,
                'clinical_question' => $clinicalQuestion,
                'status' => 'sent',
                'summary_snapshot' => [
                    'patient' => [
                        'name' => $appointment->patient?->name,
                        'age' => $profile?->age,
                        'gender' => filled($appointment->patient?->gender) ? (string) $appointment->patient->gender : null,
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
                    'patient_summary' => $packet,
                    'approved_summary' => $packet['source'] === 'approved' ? $packet['text'] : null,
                    'ai_summary' => $packet['source'] === 'analysis' ? $packet['text'] : null,
                    'referral_brief' => $packet['referral_brief'],
                    'report_ids' => $reportIds,
                ],
            ]);

            foreach ($reportIds as $reportId) {
                ReferralReport::query()->create([
                    'referral_id' => $referral->id,
                    'appointment_report_id' => $reportId,
                ]);
            }

            DB::afterCommit(function () use ($referral) {
                app(ClinicNotifier::class)->referralReceived($referral);
            });

            return $referral;
        });
    }

    /**
     * A short brief the receiving doctor can read with the attached reports.
     * Uses an approved analysis when one exists, then any completed analysis,
     * then the older visit brief, then the chart itself.
     *
     * @param  list<int>|null  $reportIds
     * @return array{
     *     text: string,
     *     source: string,
     *     highlights: list<string>,
     *     findings: list<array{title: string, explanation: string, value: string, range: string}>,
     *     steps: list<string>,
     *     limitations: ?string,
     *     report_notes: list<array{report_id: int, label: string, narrative: string, rows: list<array{parameter: string, value: string, range: string, flag: string}>}>,
     *     referral_brief: ?string
     * }
     */
    public function patientPacket(Appointment $appointment, ?array $reportIds = null): array
    {
        $appointment->loadMissing(['patient.patient_profile', 'reports', 'consultation']);

        $availableIds = $appointment->reports->pluck('id')->map(fn ($id) => (int) $id)->all();
        $reportIds = $reportIds === null
            ? $availableIds
            : array_values(array_intersect($availableIds, array_map('intval', $reportIds)));

        $analysis = $this->analysisForReferral($appointment);
        $legacy = filled($appointment->consultation?->ai_summary)
            ? trim((string) $appointment->consultation->ai_summary)
            : null;
        $output = [];
        $source = 'chart';
        $text = $this->chartSummary($appointment, $reportIds);

        if ($analysis?->approved_at) {
            $approved = $this->analysisText($analysis);

            if ($approved !== null) {
                $text = $approved;
                $source = 'approved';
                $output = is_array($analysis->structured_output) ? $analysis->structured_output : [];
            }
        } elseif ($analysis && $this->analysisText($analysis) !== null) {
            $text = $this->analysisText($analysis);
            $source = 'analysis';
            $output = is_array($analysis->structured_output) ? $analysis->structured_output : [];
        } elseif ($legacy !== null) {
            $text = $legacy;
            $source = 'analysis';
        }

        $brief = data_get($appointment->consultation?->ai_analysis, 'referral_brief');
        $brief = is_string($brief) ? trim($brief) : '';

        return [
            'text' => $text,
            'source' => $source,
            'highlights' => $this->highlights($output),
            'findings' => $this->findings($output),
            'steps' => $this->steps($output),
            'limitations' => $this->limitations($output),
            'report_notes' => $this->reportNotes($output, $reportIds),
            'referral_brief' => $brief !== '' && $brief !== $text ? $brief : null,
        ];
    }

    private function analysisForReferral(Appointment $appointment): ?AiAnalysis
    {
        $analyses = AiAnalysis::query()
            ->where('appointment_id', $appointment->id)
            ->get();

        $approved = $analyses
            ->filter(fn (AiAnalysis $row) => $row->approved_at !== null && $this->analysisText($row) !== null)
            ->sortByDesc(fn (AiAnalysis $row) => $row->approved_at?->getTimestamp() ?? 0)
            ->first();

        if ($approved) {
            return $approved;
        }

        return $analyses
            ->filter(fn (AiAnalysis $row) => $this->analysisText($row) !== null)
            ->sortByDesc('id')
            ->first();
    }

    private function analysisText(AiAnalysis $analysis): ?string
    {
        $text = $analysis->approved_summary ?: $analysis->clinician_summary ?: $analysis->original_summary;

        return filled($text) ? trim((string) $text) : null;
    }

    /**
     * @param  list<int>  $reportIds
     */
    private function chartSummary(Appointment $appointment, array $reportIds): string
    {
        $patient = $appointment->patient;
        $profile = $patient?->patient_profile;
        $name = $patient?->name ?: 'This patient';
        $age = $profile?->age;
        $gender = filled($patient?->gender) ? (string) $patient->gender : null;
        $lead = $name;

        if ($age && $gender) {
            $lead .= ' is '.$age.' years old, '.$gender;
        } elseif ($age) {
            $lead .= ' is '.$age.' years old';
        } elseif ($gender) {
            $lead .= ', '.$gender;
        }

        $date = $appointment->appointment_date?->format('j M Y');
        $reason = filled($appointment->reason) ? (string) $appointment->reason : null;
        $summary = $lead.($date ? ', seen on '.$date : '').($reason ? ' for '.$reason : '').'.';
        $diagnosis = filled($appointment->consultation?->diagnosis)
            ? (string) $appointment->consultation->diagnosis
            : null;

        if ($diagnosis) {
            $summary .= ' The chart records '.$diagnosis.'.';
        }

        $names = $appointment->reports
            ->whereIn('id', $reportIds)
            ->pluck('original_name')
            ->filter()
            ->values();

        if ($names->isNotEmpty()) {
            $summary .= ' Reports included: '.$names->implode(', ').'.';
        }

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $output
     * @return list<string>
     */
    private function highlights(array $output): array
    {
        $items = [];

        foreach (is_array($output['highlights'] ?? null) ? $output['highlights'] : [] as $item) {
            if (! is_string($item) || trim($item) === '') {
                continue;
            }

            $items[] = trim($item);

            if (count($items) === 4) {
                break;
            }
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $output
     * @return list<array{title: string, explanation: string, value: string, range: string}>
     */
    private function findings(array $output): array
    {
        $rows = [];

        foreach (is_array($output['key_findings'] ?? null) ? $output['key_findings'] : [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $title = trim((string) ($item['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            $rows[] = [
                'title' => $title,
                'explanation' => trim((string) ($item['explanation'] ?? '')),
                'value' => trim((string) ($item['observed_value'] ?? '').' '.(string) ($item['unit'] ?? '')),
                'range' => trim((string) ($item['reference_range'] ?? '')),
            ];

            if (count($rows) === 4) {
                break;
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $output
     * @return list<string>
     */
    private function steps(array $output): array
    {
        $steps = [];

        foreach (is_array($output['suggested_review_actions'] ?? null) ? $output['suggested_review_actions'] : [] as $item) {
            $text = is_array($item) ? trim((string) ($item['text'] ?? '')) : '';

            if ($text === '') {
                continue;
            }

            $steps[] = $text;

            if (count($steps) === 3) {
                break;
            }
        }

        return $steps;
    }

    /**
     * @param  array<string, mixed>  $output
     */
    private function limitations(array $output): ?string
    {
        $text = trim((string) ($output['limitations'] ?? ''));

        return $text !== '' ? $text : null;
    }

    /**
     * @param  array<string, mixed>  $output
     * @param  list<int>  $reportIds
     * @return list<array{report_id: int, label: string, narrative: string, rows: list<array{parameter: string, value: string, range: string, flag: string}>}>
     */
    private function reportNotes(array $output, array $reportIds): array
    {
        $notes = [];

        foreach (is_array($output['report_interpretations'] ?? null) ? $output['report_interpretations'] : [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = (int) ($item['source_report_id'] ?? 0);

            if ($id === 0 || ! in_array($id, $reportIds, true)) {
                continue;
            }

            $rows = [];

            foreach (array_slice(is_array($item['rows'] ?? null) ? $item['rows'] : [], 0, 8) as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $parameter = trim((string) ($row['parameter'] ?? ''));
                $value = trim((string) ($row['observed_value'] ?? '').' '.(string) ($row['unit'] ?? ''));

                if ($parameter === '' && $value === '') {
                    continue;
                }

                $rows[] = [
                    'parameter' => $parameter,
                    'value' => $value,
                    'range' => trim((string) ($row['reference_range'] ?? '')),
                    'flag' => trim((string) ($row['flag'] ?? '')),
                ];
            }

            $narrative = trim((string) ($item['narrative'] ?? ''));

            if ($narrative === '' && $rows === []) {
                continue;
            }

            $notes[] = [
                'report_id' => $id,
                'label' => trim((string) ($item['source_label'] ?? '')),
                'narrative' => $narrative,
                'rows' => $rows,
            ];

            if (count($notes) === 8) {
                break;
            }
        }

        return $notes;
    }
}
