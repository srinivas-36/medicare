<?php

namespace App\Jobs;

use App\Models\Consultation;
use App\Services\ClinicalAnalysis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Writes the visit analysis. A second consult save may queue this again.
 * Clinical notes, diagnosis, and plan are never replaced with model text.
 */
class GenerateClinicalAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $consultationId) {}

    public function handle(ClinicalAnalysis $analysis): void
    {
        $consultation = Consultation::query()->find($this->consultationId);

        if (! $consultation) {
            return;
        }

        if (blank(config('services.ai.key'))) {
            $consultation->forceFill([
                'ai_status' => 'skipped',
                'ai_summary' => null,
            ])->save();

            return;
        }

        try {
            $result = $analysis->analyze($consultation);
        } catch (Throwable) {
            $consultation->forceFill([
                'ai_status' => 'failed',
            ])->save();

            return;
        }

        $consultation->forceFill([
            'ai_status' => 'ready',
            'ai_summary' => $result['summary'],
            'ai_analysis' => $result['analysis'],
            'ai_generated_at' => now(),
        ])->save();
    }
}
