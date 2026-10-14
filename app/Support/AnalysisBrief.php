<?php

namespace App\Support;

/**
 * Turns a stored analysis into the short brief shown on the review screen.
 * Longer source text stays available in the details panel.
 */
class AnalysisBrief
{
    /**
     * @param  array<string, mixed>  $output
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function present(array $output, array $context): array
    {
        $findings = array_values(array_filter(
            is_array($output['key_findings'] ?? null) ? $output['key_findings'] : [],
            'is_array',
        ));
        $actions = array_values(array_filter(
            is_array($output['suggested_review_actions'] ?? null) ? $output['suggested_review_actions'] : [],
            'is_array',
        ));
        $summary = trim((string) ($output['clinical_summary'] ?? ''));
        $limitations = trim((string) ($output['limitations'] ?? ''));
        $background = trim((string) data_get($output, 'patient_context.background', ''));
        $symptoms = trim((string) data_get($output, 'patient_context.symptoms', ''));
        $prior = self::mentionsPrior($summary.' '.$limitations.' '.collect($findings)->pluck('explanation')->implode(' '));

        return [
            'overview' => self::overview($summary, $background, $symptoms, $limitations),
            'full_summary' => $summary,
            'limitations' => $limitations,
            'chips' => self::chips($output, $findings, $prior),
            'findings' => array_map(fn (array $finding, int $index) => self::finding($finding, $index, $prior), $findings, array_keys($findings)),
            'steps' => array_slice(array_values(array_filter(array_map(
                fn (array $action) => self::sentence((string) ($action['text'] ?? '')),
                $actions,
            ))), 0, 3),
            'source_label' => self::sourceLabel($context, $prior),
            'prior_labs' => $prior,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $findings
     * @return list<array{label: string, tone: string, icon: string}>
     */
    private static function chips(array $output, array $findings, bool $prior): array
    {
        $provided = array_values(array_filter(array_map(
            fn ($item) => is_scalar($item) ? self::chipLabel((string) $item) : '',
            is_array($output['highlights'] ?? null) ? $output['highlights'] : [],
        )));

        if ($provided !== []) {
            return array_map(fn (string $label, int $index) => [
                'label' => $label,
                'tone' => self::chipTone($label, $index),
                'icon' => self::iconFor($label),
            ], array_slice($provided, 0, 4), array_keys(array_slice($provided, 0, 4)));
        }

        $chips = [];

        foreach ($findings as $finding) {
            if (($finding['category'] ?? '') === 'extracted_measurement') {
                continue;
            }

            $label = self::chipLabel((string) ($finding['title'] ?? ''));

            if ($label === '' || collect($chips)->contains('label', $label)) {
                continue;
            }

            $chips[] = [
                'label' => $label,
                'tone' => ($finding['priority'] ?? '') === 'attention' && $chips === [] ? 'orange' : 'blue',
                'icon' => self::iconFor($label),
            ];

            if (count($chips) === 2) {
                break;
            }
        }

        if ($prior) {
            $chips[] = ['label' => 'Prior labs only', 'tone' => 'gray', 'icon' => 'file'];
        }

        return $chips;
    }

    /**
     * @param  array<string, mixed>  $finding
     * @return array<string, mixed>
     */
    private static function finding(array $finding, int $index, bool $prior = false): array
    {
        $title = trim((string) ($finding['title'] ?? 'Finding'));
        $explanation = trim((string) ($finding['explanation'] ?? ''));
        $value = trim((string) ($finding['observed_value'] ?? '').' '.(string) ($finding['unit'] ?? ''));
        $line = self::sentence($explanation);
        $badge = self::badge($finding, $explanation, $value, $prior);

        if ($value !== '' && preg_match('/\d/', $value) && ! str_contains(strtolower($line), strtolower($value))) {
            $line = $line === '' ? $value : $value.' · '.$line;
        }

        return [
            'index' => $index,
            'title' => $title,
            'line' => $line,
            'full' => $explanation,
            'value' => $value,
            'range' => trim((string) ($finding['reference_range'] ?? '')),
            'badge' => $badge['label'],
            'tone' => $badge['tone'],
            'icon' => self::iconFor($title.' '.$explanation, ($finding['category'] ?? '') === 'extracted_measurement'),
        ];
    }

    /**
     * @param  array<string, mixed>  $finding
     * @return array{label: string, tone: string}
     */
    private static function badge(array $finding, string $explanation, string $value, bool $prior = false): array
    {
        $text = strtolower($finding['title'].' '.$explanation);
        $measurement = ($finding['category'] ?? '') === 'extracted_measurement' || preg_match('/\d/', $value);

        if ($measurement && (str_contains($text, 'within') || str_contains($text, 'normal'))) {
            return [
                'label' => $prior || str_contains($text, 'prior') || str_contains($text, 'previous') ? 'Within prior range' : 'Within range',
                'tone' => 'green',
            ];
        }

        if (($finding['priority'] ?? '') === 'attention' && ! str_contains($text, 'history')) {
            return ['label' => 'Needs assessment', 'tone' => 'orange'];
        }

        if (str_contains($text, 'history') || str_contains($text, 'known') || ($finding['category'] ?? '') === 'documented_fact') {
            return ['label' => 'Relevant history', 'tone' => 'blue'];
        }

        return ['label' => 'Review', 'tone' => 'gray'];
    }

    private static function overview(string $summary, string $background, string $symptoms, string $limitations): string
    {
        $short = self::sentences($summary, 2);

        if ($short !== '' && strlen($short) <= 220) {
            return $short;
        }

        if ($background !== '' && strlen($background) <= 180) {
            $text = rtrim($background, '.');

            if ($symptoms !== '' && strlen($symptoms) <= 120 && ! str_contains(strtolower($background), strtolower(rtrim($symptoms, '.')))) {
                $text .= '. Presenting with '.lcfirst(rtrim($symptoms, '.'));
            }

            $limit = self::sentences($limitations, 1);

            if ($limit !== '' && strlen($limit) <= 160) {
                $text .= '. '.rtrim($limit, '.');
            }

            return $text.'.';
        }

        return $short !== '' ? $short : $summary;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function sourceLabel(array $context, bool $prior): string
    {
        $count = count(is_array($context['reports'] ?? null) ? $context['reports'] : []);

        if ($count === 0) {
            return 'Chart only';
        }

        $noun = $count === 1 ? 'lab report' : 'lab reports';

        return $prior ? $count.' prior '.$noun : $count.' '.$noun;
    }

    private static function mentionsPrior(string $text): bool
    {
        return (bool) preg_match('/\b(prior|previous|earlier visit|does not reflect)\b/i', $text);
    }

    private static function chipLabel(string $title): string
    {
        $title = trim(preg_replace('/\b(history|baseline|total|count|documented)\b/i', '', $title) ?? $title);
        $title = trim(preg_replace('/\s+/', ' ', $title) ?? $title);
        $words = $title === '' ? [] : preg_split('/\s+/', $title);

        if ($words === false || $words === []) {
            return '';
        }

        if (count($words) > 3) {
            $words = array_slice($words, 0, 2);
        }

        return ucfirst(strtolower(implode(' ', $words)));
    }

    private static function chipTone(string $label, int $index): string
    {
        if (preg_match('/\b(prior|previous|old|missing)\b/i', $label)) {
            return 'gray';
        }

        return $index === 0 ? 'orange' : 'blue';
    }

    private static function iconFor(string $text, bool $measurement = false): string
    {
        $text = strtolower($text);

        if (str_contains($text, 'thyroid') || str_contains($text, 'hormone')) {
            return 'thyroid';
        }

        if ($measurement || preg_match('/\b(lab|count|hemoglobin|leukocyte|blood|glucose|wbc)\b/', $text)) {
            return 'flask';
        }

        if (preg_match('/\b(fever|temperature|pain|cough)\b/', $text)) {
            return 'thermometer';
        }

        if (preg_match('/\b(prior|previous|document|source)\b/', $text)) {
            return 'file';
        }

        return 'clipboard';
    }

    private static function sentence(string $text): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        if ($text === '') {
            return '';
        }

        if (! preg_match('/^.*?[.!?](?:\s|$)/', $text, $match)) {
            return $text;
        }

        return trim($match[0]);
    }

    private static function sentences(string $text, int $count): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        if ($text === '' || ! preg_match_all('/[^.!?]+[.!?]+|[^.!?]+$/', $text, $matches)) {
            return $text;
        }

        $parts = array_values(array_filter(array_map('trim', $matches[0])));

        return implode(' ', array_slice($parts, 0, $count));
    }
}
