<?php

namespace App\Support;

/**
 * Chart-backed brief used when a visit analysis does not return a model result.
 * Every line comes from the patient, the visit, or a selected report.
 */
class ChartAnalysis
{
    /**
     * @param  array<string, mixed>  $context
     * @param  list<int>  $selectedIds
     * @return array<string, mixed>
     */
    public static function output(array $context, array $selectedIds = []): array
    {
        $patient = is_array($context['patient'] ?? null) ? $context['patient'] : [];
        $appointment = is_array($context['appointment'] ?? null) ? $context['appointment'] : [];
        $consultation = is_array($context['consultation'] ?? null) ? $context['consultation'] : [];
        $name = filled($patient['name'] ?? null) ? (string) $patient['name'] : 'Patient';
        $age = $patient['age'] ?? null;
        $gender = filled($patient['gender'] ?? null) ? (string) $patient['gender'] : null;
        $reason = filled($appointment['reason'] ?? null) ? (string) $appointment['reason'] : null;
        $date = filled($appointment['date'] ?? null) ? (string) $appointment['date'] : null;
        $diagnosis = filled($consultation['diagnosis'] ?? null) ? (string) $consultation['diagnosis'] : null;
        $notes = filled($consultation['notes'] ?? null) ? (string) $consultation['notes'] : null;
        $plan = filled($consultation['plan'] ?? null) ? (string) $consultation['plan'] : null;
        $reports = self::reports($context, $selectedIds);
        $health = self::health($context);

        $identity = $name;

        if ($age && $gender) {
            $identity .= ' is '.$age.' years old, '.$gender;
        } elseif ($age) {
            $identity .= ' is '.$age.' years old';
        } elseif ($gender) {
            $identity .= ', '.$gender;
        }

        $summary = $identity.($date ? ', seen on '.$date : '').($reason ? ' for '.$reason : '').'.';

        if ($diagnosis) {
            $summary .= ' The chart records '.$diagnosis.'.';
        }

        if ($reports !== []) {
            $summary .= ' Reports with this visit: '.implode(', ', array_column($reports, 'name')).'.';
        }

        $findings = [];

        if ($reason) {
            $findings[] = self::finding('Visit reason', 'documented_fact', 'attention', $reason);
        }

        foreach ($health as $item) {
            $measured = preg_match('/\d/', $item['value']) === 1;
            $findings[] = self::finding(
                $item['label'],
                $measured ? 'extracted_measurement' : 'documented_fact',
                'routine',
                $measured
                    ? 'Documented on the patient chart.'
                    : 'Documented as '.strtolower($item['value']).' on the patient chart.',
                $measured ? $item['value'] : '',
            );
        }

        if ($diagnosis) {
            $findings[] = self::finding('Recorded diagnosis', 'documented_fact', 'routine', $diagnosis);
        }

        if ($notes) {
            $findings[] = self::finding('Visit note', 'documented_fact', 'routine', $notes);
        }

        if ($plan) {
            $findings[] = self::finding('Recorded plan', 'documented_fact', 'routine', $plan);
        }

        $earlier = self::earlierVisits($context);

        if ($earlier !== '') {
            $findings[] = self::finding('Earlier visits', 'documented_fact', 'routine', $earlier);
        }

        $highlights = [];

        if ($reason) {
            $highlights[] = $reason;
        }

        foreach ($health as $item) {
            if (count($highlights) >= 3) {
                break;
            }

            $highlights[] = $item['label'];
        }

        if ($reports !== [] && count($highlights) < 4) {
            $highlights[] = $reports[0]['name'];
        }

        $actions = [];

        if ($reason) {
            $actions[] = ['kind' => 'verify', 'text' => 'Review the visit reason with the patient before the note.'];
        }

        if ($health !== []) {
            $actions[] = ['kind' => 'verify', 'text' => 'Confirm the charted history during the visit.'];
        }

        if ($reports !== []) {
            $actions[] = ['kind' => 'verify', 'text' => 'Open the attached reports and check them against the chart.'];
        }

        if ($actions === []) {
            $actions[] = ['kind' => 'verify', 'text' => 'Review the chart with the patient before adding a visit note.'];
        }

        return [
            'clinical_summary' => $summary,
            'highlights' => array_slice($highlights, 0, 4),
            'patient_context' => [
                'reason' => (string) ($reason ?? ''),
                'background' => $identity.($diagnosis ? '. The chart records '.$diagnosis : '').'.',
                'symptoms' => (string) (filled($context['symptoms'] ?? null) ? $context['symptoms'] : ($reason ?? '')),
            ],
            'key_findings' => $findings,
            'report_interpretations' => array_map(fn (array $report) => [
                'file_index' => null,
                'source_report_id' => $report['id'],
                'source_label' => $report['name'],
                'source_verified' => true,
                'document_type' => $report['type'],
                'report_date' => '',
                'specimen' => '',
                'narrative' => $report['name'].' is attached to this visit.',
                'extraction_note' => '',
                'rows' => [],
            ], $reports),
            'differential_considerations' => [],
            'missing_information' => array_values(array_filter(array_map(
                fn ($item) => is_scalar($item) ? trim((string) $item) : '',
                is_array($context['missing'] ?? null) ? $context['missing'] : [],
            ))),
            'suggested_review_actions' => array_slice($actions, 0, 3),
            'source_references' => array_map(fn (array $report) => [
                'kind' => 'report',
                'label' => $report['name'],
                'file_index' => null,
                'source_report_id' => $report['id'],
                'source_verified' => true,
            ], $reports),
            'limitations' => $reports === []
                ? 'Prepared from the chart recorded for this visit.'
                : 'Prepared from the chart and the reports recorded for this visit.',
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<array{label: string, value: string}>
     */
    private static function health(array $context): array
    {
        $rows = [];

        foreach (is_array($context['health'] ?? null) ? $context['health'] : [] as $item) {
            if (! is_array($item) || ! filled($item['value'] ?? null)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            $value = trim((string) $item['value']);

            if ($label === '' || $value === '') {
                continue;
            }

            $rows[] = ['label' => $label, 'value' => $value];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function earlierVisits(array $context): string
    {
        $lines = [];

        foreach (is_array($context['prior'] ?? null) ? $context['prior'] : [] as $visit) {
            if (! is_array($visit)) {
                continue;
            }

            $lines[] = trim(
                (filled($visit['date'] ?? null) ? $visit['date'] : 'Date not recorded')
                .' · '
                .(filled($visit['reason'] ?? null) ? $visit['reason'] : 'Reason not recorded')
                .(filled($visit['diagnosis'] ?? null) ? ' · '.$visit['diagnosis'] : '')
            );
        }

        return implode(' ', array_filter($lines));
    }

    /**
     * @param  array<string, mixed>  $context
     * @param  list<int>  $selectedIds
     * @return list<array{id: int, name: string, type: string}>
     */
    private static function reports(array $context, array $selectedIds): array
    {
        $selectedIds = array_values(array_unique(array_filter(array_map(
            fn ($id) => is_numeric($id) ? (int) $id : 0,
            $selectedIds,
        ))));
        $rows = [];

        foreach (is_array($context['reports'] ?? null) ? $context['reports'] : [] as $report) {
            if (! is_array($report)) {
                continue;
            }

            $id = (int) ($report['id'] ?? 0);

            if ($selectedIds !== [] && ! in_array($id, $selectedIds, true)) {
                continue;
            }

            $name = trim((string) ($report['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $rows[] = [
                'id' => $id,
                'name' => $name,
                'type' => trim((string) ($report['type'] ?? '')) ?: 'Report',
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private static function finding(
        string $title,
        string $category,
        string $priority,
        string $explanation,
        string $observed = '',
    ): array {
        return [
            'title' => $title,
            'category' => $category,
            'priority' => $priority,
            'observed_value' => $observed,
            'unit' => '',
            'reference_range' => '',
            'explanation' => $explanation,
            'uncertainty' => '',
            'page' => '',
            'file_index' => null,
            'source_report_id' => null,
            'source_label' => null,
            'source_verified' => false,
        ];
    }
}
