<?php

namespace App\Services\AI;

/**
 * Normalizes provider JSON and only keeps source links that match a selected report.
 */
class ClinicalOutputValidator
{
    /**
     * @param  list<array<string, mixed>>  $files
     * @return array<string, mixed>
     */
    public function validate(mixed $decoded, array $files): array
    {
        if (! is_array($decoded)) {
            throw new InvalidAnalysisOutput('The analysis response was not an object.');
        }

        $summary = $this->text($decoded['clinical_summary'] ?? '');

        if ($summary === '') {
            throw new InvalidAnalysisOutput('The analysis response had no clinical summary.');
        }

        $byIndex = [];

        foreach ($files as $file) {
            $byIndex[(int) $file['index']] = $file;
        }

        return [
            'clinical_summary' => $summary,
            'highlights' => array_slice($this->lines($decoded['highlights'] ?? []), 0, 4),
            'patient_context' => $this->patientContext($decoded['patient_context'] ?? []),
            'key_findings' => $this->findings($decoded['key_findings'] ?? [], $byIndex),
            'report_interpretations' => $this->interpretations($decoded['report_interpretations'] ?? [], $byIndex),
            'differential_considerations' => $this->differentials($decoded['differential_considerations'] ?? []),
            'missing_information' => $this->lines($decoded['missing_information'] ?? []),
            'suggested_review_actions' => $this->actions($decoded['suggested_review_actions'] ?? []),
            'source_references' => $this->sources($decoded['source_references'] ?? [], $byIndex),
            'limitations' => $this->text($decoded['limitations'] ?? ''),
        ];
    }

    /**
     * @return array{reason: string, background: string, symptoms: string}
     */
    private function patientContext(mixed $value): array
    {
        $context = is_array($value) ? $value : [];

        return [
            'reason' => $this->text($context['reason'] ?? ''),
            'background' => $this->text($context['background'] ?? ''),
            'symptoms' => $this->text($context['symptoms'] ?? ''),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $byIndex
     * @return list<array<string, mixed>>
     */
    private function findings(mixed $value, array $byIndex): array
    {
        if (! is_array($value)) {
            return [];
        }

        $rows = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $title = $this->text($item['title'] ?? $item['finding'] ?? '');

            if ($title === '') {
                continue;
            }

            $category = $this->enum($item['category'] ?? '', ['documented_fact', 'extracted_measurement', 'interpretation'], 'interpretation');
            $priority = $this->enum($item['priority'] ?? '', ['attention', 'routine', 'uncertain'], 'uncertain');
            $source = $this->source($item['file_index'] ?? null, $byIndex);

            $rows[] = [
                'title' => $title,
                'category' => $category,
                'priority' => $priority,
                'observed_value' => $this->text($item['observed_value'] ?? ''),
                'unit' => $this->text($item['unit'] ?? ''),
                'reference_range' => $this->text($item['reference_range'] ?? ''),
                'explanation' => $this->text($item['explanation'] ?? ''),
                'uncertainty' => $this->text($item['uncertainty'] ?? ''),
                'page' => $this->text($item['page'] ?? ''),
                'file_index' => $source['file_index'],
                'source_report_id' => $source['source_report_id'],
                'source_label' => $source['source_label'],
                'source_verified' => $source['source_verified'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $byIndex
     * @return list<array<string, mixed>>
     */
    private function interpretations(mixed $value, array $byIndex): array
    {
        if (! is_array($value)) {
            return [];
        }

        $rows = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $source = $this->source($item['file_index'] ?? null, $byIndex);
            $parameters = [];

            foreach (is_array($item['rows'] ?? null) ? $item['rows'] : [] as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $parameter = $this->text($row['parameter'] ?? '');
                $observed = $this->text($row['observed_value'] ?? '');

                if ($parameter === '' && $observed === '') {
                    continue;
                }

                $parameters[] = [
                    'parameter' => $parameter,
                    'observed_value' => $observed,
                    'unit' => $this->text($row['unit'] ?? ''),
                    'reference_range' => $this->text($row['reference_range'] ?? ''),
                    'flag' => $this->enum($row['flag'] ?? '', ['low', 'high', 'normal', 'unknown'], 'unknown'),
                ];
            }

            $narrative = $this->text($item['narrative'] ?? '');

            if ($narrative === '' && $parameters === [] && ! $source['source_verified']) {
                continue;
            }

            $rows[] = [
                'file_index' => $source['file_index'],
                'source_report_id' => $source['source_report_id'],
                'source_label' => $source['source_label'],
                'source_verified' => $source['source_verified'],
                'document_type' => $this->text($item['document_type'] ?? ''),
                'report_date' => $this->text($item['report_date'] ?? ''),
                'specimen' => $this->text($item['specimen'] ?? ''),
                'narrative' => $narrative,
                'extraction_note' => $this->text($item['extraction_note'] ?? ''),
                'rows' => $parameters,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{possibility: string, supporting_evidence: string, conflicting_or_missing: string, questions: string, follow_up: string}>
     */
    private function differentials(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $rows = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $possibility = $this->text($item['possibility'] ?? '');

            if ($possibility === '') {
                continue;
            }

            $rows[] = [
                'possibility' => $possibility,
                'supporting_evidence' => $this->text($item['supporting_evidence'] ?? ''),
                'conflicting_or_missing' => $this->text($item['conflicting_or_missing'] ?? ''),
                'questions' => $this->text($item['questions'] ?? ''),
                'follow_up' => $this->text($item['follow_up'] ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{kind: string, text: string}>
     */
    private function actions(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $rows = [];

        foreach ($value as $item) {
            if (is_string($item) && trim($item) !== '') {
                $rows[] = ['kind' => 'verify', 'text' => trim($item)];

                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            $text = $this->text($item['text'] ?? '');

            if ($text === '') {
                continue;
            }

            $rows[] = [
                'kind' => $this->enum($item['kind'] ?? '', ['clarify', 'verify', 'investigate', 'follow_up'], 'verify'),
                'text' => $text,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $byIndex
     * @return list<array<string, mixed>>
     */
    private function sources(mixed $value, array $byIndex): array
    {
        if (! is_array($value)) {
            return [];
        }

        $rows = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = $this->text($item['label'] ?? '');
            $source = $this->source($item['file_index'] ?? null, $byIndex);

            if ($label === '' && ! $source['source_verified']) {
                continue;
            }

            $rows[] = [
                'kind' => $this->enum($item['kind'] ?? '', ['report', 'record'], 'record'),
                'label' => $label !== '' ? $label : (string) $source['source_label'],
                'file_index' => $source['file_index'],
                'source_report_id' => $source['source_report_id'],
                'source_verified' => $source['source_verified'],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $byIndex
     * @return array{file_index: ?int, source_report_id: ?int, source_label: ?string, source_verified: bool}
     */
    private function source(mixed $index, array $byIndex): array
    {
        $number = is_numeric($index) ? (int) $index : 0;
        $file = $byIndex[$number] ?? null;

        if (! is_array($file)) {
            return [
                'file_index' => null,
                'source_report_id' => null,
                'source_label' => null,
                'source_verified' => false,
            ];
        }

        return [
            'file_index' => $number,
            'source_report_id' => (int) $file['report_id'],
            'source_label' => (string) $file['label'],
            'source_verified' => true,
        ];
    }

    /**
     * @param  list<string>  $allowed
     */
    private function enum(mixed $value, array $allowed, string $fallback): string
    {
        $text = strtolower($this->text($value));

        return in_array($text, $allowed, true) ? $text : $fallback;
    }

    /**
     * @return list<string>
     */
    private function lines(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split("/\r\n|\n|\r/", $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => is_scalar($item) ? trim((string) $item) : '',
            $value,
        )));
    }

    private function text(mixed $value): string
    {
        if (is_array($value)) {
            return trim(implode(' ', array_map(fn ($item) => is_scalar($item) ? (string) $item : '', $value)));
        }

        return trim((string) $value);
    }
}
