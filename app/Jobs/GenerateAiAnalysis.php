<?php

namespace App\Jobs;

use App\Models\AiAnalysis;
use App\Services\AI\ClinicalAnalysisService;
use App\Services\AI\ClinicalOutputValidator;
use App\Services\AI\InvalidAnalysisOutput;
use App\Services\AI\MedicalReportExtractor;
use App\Services\AI\PatientContextBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateAiAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 150;

    public int $tries = 1;

    public function __construct(public int $analysisId) {}

    public function handle(
        PatientContextBuilder $context,
        MedicalReportExtractor $extractor,
        ClinicalAnalysisService $service,
        ClinicalOutputValidator $validator,
    ): void {
        $analysis = AiAnalysis::query()->with('appointment.reports')->find($this->analysisId);

        if (! $analysis || ! $analysis->isOpen() || ! $analysis->appointment) {
            return;
        }

        if (blank(config('services.ai.key'))) {
            $this->fail($analysis, 'not_configured');

            return;
        }

        try {
            $this->step($analysis, 'preparing');
            $ids = array_map('intval', $analysis->selected_report_ids ?? []);
            $reports = $analysis->appointment->reports->whereIn('id', $ids)->values();

            $this->step($analysis, 'processing_reports');
            $files = $extractor->extract($reports);

            $this->step($analysis, 'generating');
            $analysis->forceFill([
                'context_snapshot' => $context->snapshot($analysis->appointment, $files),
                'provider' => 'gemini',
                'model' => $service->model(),
                'prompt_version' => ClinicalAnalysisService::PROMPT_VERSION,
            ])->save();

            $decoded = $service->generate($analysis->appointment, $files);

            $this->step($analysis, 'validating');
            $output = $validator->validate($decoded, $files);
            $analysis->forceFill([
                'status' => 'completed',
                'progress' => 'validating',
                'structured_output' => $output,
                'original_summary' => $output['clinical_summary'],
                'completed_at' => now(),
                'error_code' => null,
            ])->save();

            $analysis->audits()->create([
                'user_id' => $analysis->initiated_by,
                'action' => 'completed',
                'meta' => ['report_count' => count($files)],
            ]);
        } catch (InvalidAnalysisOutput) {
            $this->fail($analysis, 'invalid_output');
        } catch (Throwable $exception) {
            Log::warning('Clinical analysis failed.', [
                'analysis_id' => $analysis->id,
                'error' => $exception::class,
            ]);

            $this->fail($analysis, 'provider_error');
        }
    }

    private function step(AiAnalysis $analysis, string $step): void
    {
        $analysis->forceFill([
            'status' => $step,
            'progress' => $step,
            'started_at' => $analysis->started_at ?? now(),
        ])->save();
    }

    private function fail(AiAnalysis $analysis, string $code): void
    {
        $analysis->forceFill([
            'status' => 'failed',
            'error_code' => $code,
            'completed_at' => now(),
        ])->save();

        $analysis->audits()->create([
            'user_id' => $analysis->initiated_by,
            'action' => 'failed',
            'meta' => ['error_code' => $code],
        ]);
    }
}
