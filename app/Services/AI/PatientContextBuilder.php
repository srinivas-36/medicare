<?php

namespace App\Services\AI;

use App\Models\Appointment;
use App\Models\AppointmentReport;
use App\Services\PatientHistory;
use App\Support\AppointmentPage;
use Illuminate\Support\Collection;

/**
 * Facts the doctor reviews before an analysis, rebuilt on the server when the job runs.
 * Emails, phones, storage paths, and payment ids are left out.
 */
class PatientContextBuilder
{
    public function __construct(private PatientHistory $history) {}

    /**
     * @param  Collection<int, AppointmentReport>|null  $reports
     * @return array<string, mixed>
     */
    public function preview(Appointment $appointment, ?Collection $reports = null): array
    {
        $appointment->loadMissing(['patient.patient_profile', 'consultation', 'reports', 'doctor.user']);

        $patient = $appointment->patient;
        $profile = $patient?->patient_profile;
        $health = is_array($profile?->health_notes) ? $profile->health_notes : [];
        $consultation = $appointment->consultation;
        $reports = $reports ?? $appointment->reports;

        $prior = $this->history->priorCompleted(
            (int) $appointment->patient_id,
            (int) $appointment->doctor_id,
            $appointment,
        );

        return [
            'patient' => [
                'name' => $patient?->name ?: null,
                'reference' => $patient ? (string) $patient->id : null,
                'age' => $profile?->age,
                'gender' => filled($patient?->gender) ? (string) $patient->gender : null,
                'blood_type' => $profile?->blood_type ?: null,
            ],
            'appointment' => [
                'id' => $appointment->id,
                'date' => $appointment->appointment_date?->format('j M Y'),
                'status' => AppointmentPage::status($appointment->status)['label'] ?? null,
                'visit_type' => $appointment->visit_type ?: null,
                'reason' => filled($appointment->reason) ? (string) $appointment->reason : null,
            ],
            'symptoms' => filled($appointment->reason) ? (string) $appointment->reason : null,
            'duration' => null,
            'health' => [
                ['label' => 'Blood pressure', 'value' => $this->healthValue($health, 'bp')],
                ['label' => 'Diabetic', 'value' => $this->healthValue($health, 'diabetic')],
                ['label' => 'Hyperthyroidism', 'value' => $this->healthValue($health, 'hyperthyroidism')],
            ],
            'consultation' => [
                'notes' => $this->blank($consultation?->clinical_notes),
                'diagnosis' => $this->blank($consultation?->diagnosis),
                'plan' => $this->blank($consultation?->plan),
            ],
            'prior' => $prior->map(function (Appointment $visit) {
                return [
                    'date' => $visit->appointment_date?->format('j M Y'),
                    'reason' => $this->blank($visit->reason),
                    'diagnosis' => $this->blank($visit->consultation?->diagnosis),
                    'plan' => $this->blank($visit->consultation?->plan),
                    'reports' => $visit->reports->map(fn (AppointmentReport $report) => $this->label($report))->values()->all(),
                ];
            })->values()->all(),
            'reports' => $reports->values()->map(function (AppointmentReport $report) use ($appointment) {
                return [
                    'id' => $report->id,
                    'name' => $this->label($report),
                    'type' => $report->group_name ?: 'Report',
                    'uploaded' => $report->created_at?->format('j M Y, g:i A'),
                    'supported' => MedicalReportExtractor::supported($report),
                    'url' => route('doctor.appointments.reports.show', [$appointment, $report]),
                ];
            })->all(),
            'missing' => array_values(array_filter([
                $this->blank($appointment->reason) === null ? 'Symptoms and duration are not recorded for this visit.' : 'Symptom duration is not recorded separately from the visit reason.',
                'Medications are not recorded on this chart.',
                'Allergies are not recorded on this chart.',
            ])),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $files
     */
    public function narrative(Appointment $appointment, array $files): string
    {
        $context = $this->preview($appointment, $appointment->reports->whereIn('id', array_column($files, 'report_id')));
        $lines = [
            'Patient: '.($context['patient']['name'] ?: 'not recorded'),
            'Patient reference: '.($context['patient']['reference'] ?: 'not recorded'),
            'Age: '.($context['patient']['age'] ?? 'not recorded'),
            'Gender: '.($context['patient']['gender'] ?: 'not recorded'),
            'Blood type: '.($context['patient']['blood_type'] ?: 'not recorded'),
        ];

        foreach ($context['health'] as $item) {
            $lines[] = $item['label'].': '.($item['value'] ?: 'not recorded');
        }

        $lines[] = 'Visit date: '.($context['appointment']['date'] ?: 'not recorded');
        $lines[] = 'Visit type: '.($context['appointment']['visit_type'] ?: 'not recorded');
        $lines[] = 'Reason and symptoms: '.($context['symptoms'] ?: 'not recorded');
        $lines[] = 'Symptom duration: not recorded';
        $lines[] = 'Clinical notes: '.($context['consultation']['notes'] ?: 'none');
        $lines[] = 'Diagnosis already recorded by the doctor: '.($context['consultation']['diagnosis'] ?: 'none');
        $lines[] = 'Plan already recorded by the doctor: '.($context['consultation']['plan'] ?: 'none');

        $priorLines = collect($context['prior'])->map(function (array $visit) {
            $reports = $visit['reports'] === [] ? 'none' : implode(', ', $visit['reports']);

            return implode(' | ', [
                $visit['date'] ?: '',
                'Reason: '.($visit['reason'] ?: 'none'),
                'Diagnosis: '.($visit['diagnosis'] ?: 'none'),
                'Plan: '.($visit['plan'] ?: 'none'),
                'Reports: '.$reports,
            ]);
        })->implode("\n");

        $lines[] = "Earlier completed visits with this doctor:\n".($priorLines !== '' ? $priorLines : 'none');

        if ($files === []) {
            $lines[] = 'Selected reports: none';
        } else {
            $reportLines = [];

            foreach ($files as $file) {
                $reportLines[] = $file['index'].'. '.$file['group'].': '.$file['label'].' ('.$file['status'].')';
            }

            $lines[] = "Selected reports:\n".implode("\n", $reportLines);
        }

        $lines[] = 'Medications: not recorded';
        $lines[] = 'Allergies: not recorded';

        return implode("\n", $lines);
    }

    /**
     * @param  list<array<string, mixed>>  $files
     * @return array<string, mixed>
     */
    public function snapshot(Appointment $appointment, array $files): array
    {
        $preview = $this->preview($appointment);

        $preview['selected_reports'] = array_map(fn (array $file) => [
            'id' => $file['report_id'],
            'label' => $file['label'],
            'group' => $file['group'],
            'extraction' => $file['status'],
        ], $files);

        unset($preview['reports']);

        return $preview;
    }

    /**
     * @param  array<string, mixed>  $health
     */
    private function healthValue(array $health, string $key): ?string
    {
        $item = $health[$key] ?? null;

        if (! is_array($item)) {
            return null;
        }

        if (array_key_exists('known', $item) && $item['known'] === false) {
            return 'Not known';
        }

        $value = $item['value'] ?? null;

        if ($value === 'yes') {
            return 'Yes';
        }

        if ($value === 'no') {
            return 'No';
        }

        return filled($value) ? (string) $value : null;
    }

    private function blank(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : $text;
    }

    private function label(AppointmentReport $report): string
    {
        $name = trim((string) $report->original_name);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $machine = $name === ''
            || preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $base) === 1
            || preg_match('/^[A-Za-z0-9]{32,}$/', $base) === 1;

        if ($machine) {
            return trim((string) $report->group_name) ?: 'Report';
        }

        return $name;
    }
}
