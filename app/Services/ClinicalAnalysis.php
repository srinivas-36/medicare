<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentReport;
use App\Models\Consultation;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Visit brief for Gemini. Storage paths, emails, phones, and payment ids
 * stay out of the request. Readable report files are attached by name only.
 */
class ClinicalAnalysis
{
    private const FILE_LIMIT = 4 * 1024 * 1024;

    private const REQUEST_LIMIT = 12 * 1024 * 1024;

    public function __construct(private PatientHistory $history) {}

    /**
     * @return array{summary: string, analysis: array<string, mixed>}
     */
    public function analyze(Consultation $consultation): array
    {
        $consultation->loadMissing([
            'appointment.patient.patient_profile',
            'appointment.reports',
        ]);

        $response = $this->request([
            'systemInstruction' => [
                'parts' => [
                    ['text' => $this->instructions()],
                ],
            ],
            'contents' => [[
                'role' => 'user',
                'parts' => $this->parts($consultation),
            ]],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json',
                'responseSchema' => $this->responseSchema(),
            ],
        ]);

        return $this->parse($this->responseText($response->json()));
    }

    public function payload(Consultation $consultation): string
    {
        $consultation->loadMissing([
            'appointment.patient.patient_profile',
            'appointment.reports',
        ]);

        $appointment = $consultation->appointment;
        $profile = $appointment?->patient?->patient_profile;
        $health = is_array($profile?->health_notes) ? $profile->health_notes : [];

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
            'Patient: '.($appointment?->patient?->name ?: 'not recorded'),
            'Age: '.($profile?->age ?? 'not recorded'),
            'Gender: '.(filled($appointment?->patient?->gender) ? (string) $appointment->patient->gender : 'not recorded'),
            'Blood type: '.($profile?->blood_type ?: 'not recorded'),
            'Blood pressure: '.$this->healthValue($health, 'bp'),
            'Diabetic: '.$this->healthValue($health, 'diabetic'),
            'Hyperthyroidism: '.$this->healthValue($health, 'hyperthyroidism'),
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

    /**
     * @return list<array<string, mixed>>
     */
    private function parts(Consultation $consultation): array
    {
        $parts = [
            ['text' => $this->payload($consultation)],
        ];

        $budget = self::REQUEST_LIMIT;
        $number = 0;

        foreach ($consultation->appointment?->reports ?? [] as $report) {
            $number++;
            $label = $this->label($report, $number);
            $file = $this->attachment($report);

            if ($file === null || strlen($file['contents']) > $budget) {
                $parts[] = [
                    'text' => 'File '.$number.' could not be opened. Group: '.$report->group_name.'. Label: '.$label.'. Do not invent results for this file.',
                ];

                continue;
            }

            $budget -= strlen($file['contents']);
            $parts[] = [
                'text' => 'File '.$number.' is attached below. Group: '.$report->group_name.'. Label: '.$label.'. fileIndex: '.$number.'. Read every printed result in this file.',
            ];
            $parts[] = [
                'inlineData' => [
                    'mimeType' => $file['mime'],
                    'data' => base64_encode($file['contents']),
                ],
            ];
        }

        return $parts;
    }

    /**
     * @return array{mime: string, contents: string}|null
     */
    private function attachment(AppointmentReport $report): ?array
    {
        $path = (string) $report->path;
        $mime = $this->mime((string) $report->original_name) ?? $this->mime($path);

        if ($path === '' || str_contains($path, '..') || $mime === null) {
            return null;
        }

        $disk = null;

        foreach (['local', 'public'] as $name) {
            if (Storage::disk($name)->exists($path)) {
                $disk = $name;
                break;
            }
        }

        if ($disk === null) {
            return null;
        }

        $size = Storage::disk($disk)->size($path);

        if ($size <= 0 || $size > self::FILE_LIMIT) {
            return null;
        }

        $contents = Storage::disk($disk)->get($path);

        if (! is_string($contents) || $contents === '') {
            return null;
        }

        return [
            'mime' => $mime,
            'contents' => $contents,
        ];
    }

    private function mime(string $filename): ?string
    {
        return match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'txt' => 'text/plain',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $health
     */
    private function healthValue(array $health, string $key): string
    {
        $item = $health[$key] ?? null;

        if (! is_array($item)) {
            return 'not recorded';
        }

        if (array_key_exists('known', $item) && $item['known'] === false) {
            return 'not known';
        }

        $value = $item['value'] ?? null;

        if ($value === 'yes') {
            return 'Yes';
        }

        if ($value === 'no') {
            return 'No';
        }

        return filled($value) ? (string) $value : 'not recorded';
    }

    private function reportLines(?Appointment $appointment): string
    {
        if (! $appointment || $appointment->reports->isEmpty()) {
            return 'none';
        }

        return $appointment->reports
            ->values()
            ->map(fn (AppointmentReport $report, int $index) => ($index + 1).'. '.$report->group_name.': '.$this->label($report, $index + 1))
            ->implode("\n");
    }

    private function label(AppointmentReport $report, int $number): string
    {
        $name = trim((string) $report->original_name);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $machine = $name === ''
            || preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $base) === 1
            || preg_match('/^[A-Za-z0-9]{32,}$/', $base) === 1;

        if ($machine) {
            return trim((string) $report->group_name).' file '.$number;
        }

        return $name;
    }

    private function instructions(): string
    {
        return implode(' ', [
            'You are preparing a visit brief for the treating doctor from the patient details and the attached report files.',
            'Read each attached file. Copy every printed result into findings: one row per test or measurement, with the name, the printed value, the unit, the printed reference range, and flag.',
            'flag is low, high, normal, or unknown. Use low or high only when the printed range shows the value is outside it. Use unknown when no range is printed.',
            'Do not invent a number, unit, range, or diagnosis that is not in the file or the supplied facts.',
            'If a file cannot be read, return that fileIndex with an empty findings list and say so in analysis.',
            'analysis is two or three sentences for that file: what the report is, which results are outside the printed range, and what the doctor should notice for this visit. Do not repeat every normal number there; those belong in findings.',
            'overallSummary is 2 to 4 sentences on why the patient is here and which report findings matter. Do not paste the full table into the summary.',
            'warnings lists only out-of-range or unreadable items for that file. Leave warnings empty when every copied result is inside its printed range.',
            'doctorRecommendations are practical next steps: questions to ask, what to confirm, when to repeat a test. Do not prescribe doses or invent medications.',
            'Use the supplied file label. Do not mention storage names, UUIDs, paths, emails, phone numbers, payment ids, or file URLs.',
            'patientInfo uses string fields name, age, gender, bloodPressure, diabetic, and hyperthyroidism. Use "not recorded" when unknown.',
            'referralBrief is a short brief another doctor could read without the chart.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function responseSchema(): array
    {
        $string = ['type' => 'STRING'];

        return [
            'type' => 'OBJECT',
            'required' => ['patientInfo', 'overallSummary', 'reportAnalyses', 'doctorRecommendations', 'referralBrief'],
            'properties' => [
                'patientInfo' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'name' => $string,
                        'age' => $string,
                        'gender' => $string,
                        'bloodPressure' => $string,
                        'diabetic' => $string,
                        'hyperthyroidism' => $string,
                    ],
                ],
                'overallSummary' => $string,
                'reportAnalyses' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'required' => ['fileIndex', 'groupName', 'fileName', 'analysis', 'findings', 'warnings'],
                        'properties' => [
                            'fileIndex' => ['type' => 'INTEGER'],
                            'groupName' => $string,
                            'fileName' => $string,
                            'analysis' => $string,
                            'findings' => [
                                'type' => 'ARRAY',
                                'items' => [
                                    'type' => 'OBJECT',
                                    'required' => ['name', 'value', 'unit', 'range', 'flag'],
                                    'properties' => [
                                        'name' => $string,
                                        'value' => $string,
                                        'unit' => $string,
                                        'range' => $string,
                                        'flag' => $string,
                                    ],
                                ],
                            ],
                            'warnings' => [
                                'type' => 'ARRAY',
                                'items' => $string,
                            ],
                        ],
                    ],
                ],
                'doctorRecommendations' => [
                    'type' => 'ARRAY',
                    'items' => $string,
                ],
                'referralBrief' => $string,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function request(array $body): Response
    {
        $last = null;

        foreach ($this->models() as $model) {
            foreach ([true, false] as $withSchema) {
                $payload = $body;

                if (! $withSchema) {
                    unset($payload['generationConfig']['responseSchema']);
                }

                $response = Http::retry(2, 1000, function ($exception) {
                    return $exception instanceof ConnectionException;
                }, false)
                    ->withHeaders([
                        'x-goog-api-key' => (string) config('services.ai.key'),
                    ])
                    ->acceptJson()
                    ->timeout(90)
                    ->post($this->endpoint($model), $payload);

                if ($response->successful()) {
                    return $response;
                }

                $last = $response;

                if ($withSchema && $response->status() === 400) {
                    continue;
                }

                if (in_array($response->status(), [404, 429, 503], true)) {
                    break;
                }

                break 2;
            }
        }

        if ($last) {
            $last->throw();
        }

        throw new RuntimeException('The analysis request was not sent.');
    }

    /**
     * @return list<string>
     */
    private function models(): array
    {
        $models = array_filter([
            (string) config('services.ai.model'),
            ...(array) config('services.ai.fallback_models', []),
        ]);

        return array_values(array_unique($models));
    }

    private function endpoint(string $model): string
    {
        $base = rtrim((string) config('services.ai.base_url'), '/');

        return $base.'/models/'.rawurlencode($model).':generateContent';
    }

    private function responseText(mixed $body): string
    {
        $parts = data_get($body, 'candidates.0.content.parts', []);
        $text = '';

        if (is_array($parts)) {
            foreach ($parts as $part) {
                if (is_array($part) && is_string($part['text'] ?? null)) {
                    $text .= $part['text'];
                }
            }
        }

        $text = trim($text);

        if ($text === '') {
            throw new RuntimeException('The analysis response had no content.');
        }

        return $text;
    }

    /**
     * @return array{summary: string, analysis: array<string, mixed>}
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

        $summary = $this->text($decoded['overallSummary'] ?? $decoded['summary'] ?? '');
        $recommendations = $this->lines($decoded['doctorRecommendations'] ?? $decoded['clinical_recommendations'] ?? []);
        $brief = $this->text($decoded['referralBrief'] ?? $decoded['referral_brief'] ?? '');
        $gemini = $this->usesGeminiShape($decoded);

        $analysis = [
            'referral_brief' => $brief,
            'clinical_recommendations' => $recommendations,
        ];

        if ($gemini) {
            $analysis['patientInfo'] = $this->patientInfo($decoded['patientInfo'] ?? []);
            $analysis['overallSummary'] = $summary;
            $analysis['reportAnalyses'] = $this->reportAnalyses($decoded['reportAnalyses'] ?? []);
            $analysis['doctorRecommendations'] = $recommendations;
        } else {
            $analysis['history_relevant_to_this_doctor'] = $this->text($decoded['history_relevant_to_this_doctor'] ?? '');
            $analysis['report_inventory'] = $this->text($decoded['report_inventory'] ?? '');
            $analysis['points_for_the_doctor'] = $this->text($decoded['points_for_the_doctor'] ?? '');
            $analysis['clinical_picture'] = $this->text($decoded['clinical_picture'] ?? '');
            $analysis['report_reviews'] = $this->reviews($decoded['report_reviews'] ?? []);
        }

        return [
            'summary' => $summary,
            'analysis' => $analysis,
        ];
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private function usesGeminiShape(array $decoded): bool
    {
        foreach (['patientInfo', 'reportAnalyses', 'overallSummary', 'doctorRecommendations'] as $key) {
            if (array_key_exists($key, $decoded)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{name: string, age: string, gender: string, bloodPressure: string, diabetic: string, hyperthyroidism: string}
     */
    private function patientInfo(mixed $value): array
    {
        $info = is_array($value) ? $value : [];

        return [
            'name' => $this->text($info['name'] ?? ''),
            'age' => $this->text($info['age'] ?? ''),
            'gender' => $this->text($info['gender'] ?? ''),
            'bloodPressure' => $this->text($info['bloodPressure'] ?? ''),
            'diabetic' => $this->text($info['diabetic'] ?? ''),
            'hyperthyroidism' => $this->text($info['hyperthyroidism'] ?? ''),
        ];
    }

    /**
     * @return list<array{groupName: string, reportUrls: array{}, analysis: string, warnings: list<string>}>
     */
    private function reportAnalyses(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $reports = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $reports[] = [
                'fileIndex' => (int) ($item['fileIndex'] ?? 0),
                'groupName' => $this->text($item['groupName'] ?? $item['group'] ?? ''),
                'fileName' => $this->text($item['fileName'] ?? $item['file'] ?? ''),
                'reportUrls' => [],
                'analysis' => $this->text($item['analysis'] ?? $item['reading'] ?? ''),
                'findings' => $this->findings($item['findings'] ?? []),
                'warnings' => $this->lines($item['warnings'] ?? $item['attention'] ?? []),
            ];
        }

        return $reports;
    }

    /**
     * @return list<array{name: string, value: string, unit: string, range: string, flag: string}>
     */
    private function findings(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $rows = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = $this->text($item['name'] ?? '');
            $result = $this->text($item['value'] ?? '');

            if ($name === '' && $result === '') {
                continue;
            }

            $flag = strtolower($this->text($item['flag'] ?? ''));

            if (! in_array($flag, ['low', 'high', 'normal', 'unknown'], true)) {
                $flag = 'unknown';
            }

            $rows[] = [
                'name' => $name,
                'value' => $result,
                'unit' => $this->text($item['unit'] ?? ''),
                'range' => $this->text($item['range'] ?? ''),
                'flag' => $flag,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{group: string, file: string, reading: string, attention: string}>
     */
    private function reviews(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $reviews = [];

        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $reviews[] = [
                'group' => $this->text($item['group'] ?? ''),
                'file' => $this->text($item['file'] ?? ''),
                'reading' => $this->text($item['reading'] ?? ''),
                'attention' => $this->text($item['attention'] ?? ''),
            ];
        }

        return $reviews;
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
            return trim(implode("\n", array_map(function ($item) {
                return is_scalar($item) ? (string) $item : (string) json_encode($item);
            }, $value)));
        }

        return trim((string) $value);
    }
}
