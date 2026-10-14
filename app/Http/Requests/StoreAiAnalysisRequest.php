<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Policies\AiAnalysisPolicy;
use App\Services\AI\MedicalReportExtractor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAiAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('appointment');

        return $appointment instanceof Appointment
            && app(AiAnalysisPolicy::class)->manage($this->user(), $appointment);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'report_ids' => ['nullable', 'array'],
            'report_ids.*' => ['integer'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $appointment = $this->route('appointment');

            if (! $appointment instanceof Appointment || $validator->errors()->isNotEmpty()) {
                return;
            }

            $ids = array_values(array_unique(array_map('intval', $this->input('report_ids', []))));
            $reports = $appointment->reports()->whereIn('id', $ids)->get();

            if ($reports->count() !== count($ids)) {
                $validator->errors()->add('report_ids', 'Select only reports from this visit.');

                return;
            }

            $unsupported = $reports->first(fn ($report) => ! MedicalReportExtractor::supported($report));

            if ($unsupported) {
                $validator->errors()->add('report_ids', 'One of the selected reports is not a supported file type.');
            }
        });
    }

    /**
     * @return list<int>
     */
    public function reportIds(): array
    {
        return array_values(array_unique(array_map('intval', $this->validated('report_ids', []))));
    }
}
