<?php

namespace App\Http\Requests;

use App\Models\AiAnalysis;
use App\Policies\AiAnalysisPolicy;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAiAnalysisReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $analysis = $this->route('analysis');

        return $analysis instanceof AiAnalysis
            && in_array($analysis->status, ['completed', 'failed'], true)
            && app(AiAnalysisPolicy::class)->view($this->user(), $analysis);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'clinician_summary' => ['required', 'string', 'max:8000'],
            'doctor_notes' => ['nullable', 'string', 'max:4000'],
            'reviewed' => ['nullable', 'array'],
            'reviewed.*' => ['integer', 'min:0'],
        ];
    }
}
