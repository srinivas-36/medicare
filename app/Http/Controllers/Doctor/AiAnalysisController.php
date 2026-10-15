<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAiAnalysisRequest;
use App\Http\Requests\UpdateAiAnalysisReviewRequest;
use App\Jobs\GenerateAiAnalysis;
use App\Models\AiAnalysis;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\User;
use App\Policies\AiAnalysisPolicy;
use App\Services\AI\PatientContextBuilder;
use App\Support\AppointmentPage;
use App\Support\ChartAnalysis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AiAnalysisController extends Controller
{
    public function __construct(private PatientContextBuilder $context) {}

    public function show(Request $request, Appointment $appointment, ?AiAnalysis $analysis = null): View
    {
        $this->permit($request, $appointment);
        $this->belongs($appointment, $analysis);

        $appointment->load([
            'patient.patient_profile',
            'doctor.user',
            'reports',
            'consultation',
            'analyses' => fn ($query) => $query->with('approver')->latest('id'),
        ]);

        $selected = $analysis ?? $appointment->analyses->first();
        $context = $this->context->preview($appointment);

        if ($selected) {
            $this->applyChartBrief($selected, $context);
            $selected->syncOriginal();
        }

        return view('dashboard.doctor.analysis.show', [
            'appointment' => $appointment,
            'context' => $context,
            'analyses' => $appointment->analyses,
            'analysis' => $selected,
            'legacy' => $this->legacy($appointment->consultation),
            'backUrl' => route('doctor.appointments.show', $appointment),
        ]);
    }

    public function forPatient(Request $request, User $patient): View
    {
        $doctor = $request->user()->doctor;

        abort_unless($doctor && $patient->role === 'patient', 404);

        $linked = $doctor->registeredPatients()->where('users.id', $patient->id)->exists();
        $hasVisit = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->exists();

        abort_if(! $linked && ! $hasVisit, 404);

        $analyses = AiAnalysis::query()
            ->with('appointment')
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->orderByDesc('id')
            ->get();

        $latest = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->orderByDesc('id')
            ->first();

        return view('dashboard.doctor.patients.analyses', [
            'patient' => $patient,
            'analyses' => $analyses,
            'startUrl' => $latest ? route('doctor.appointments.analysis', $latest) : null,
        ]);
    }

    public function store(StoreAiAnalysisRequest $request, Appointment $appointment): RedirectResponse
    {
        $this->permit($request, $appointment);

        $started = false;

        $analysis = DB::transaction(function () use ($request, $appointment, &$started) {
            $open = AiAnalysis::query()
                ->where('appointment_id', $appointment->id)
                ->whereIn('status', AiAnalysis::OPEN)
                ->lockForUpdate()
                ->first();

            if ($open && $open->isStale()) {
                $open->forceFill([
                    'status' => 'failed',
                    'error_code' => 'provider_error',
                    'completed_at' => now(),
                ])->save();
                $open = null;
            }

            if ($open && ! $open->hasStarted()) {
                $open->forceFill([
                    'selected_report_ids' => $request->reportIds(),
                    'initiated_by' => $request->user()->id,
                ])->save();
                $started = true;

                return $open;
            }

            if ($open) {
                return $open;
            }

            $started = true;

            $created = AiAnalysis::query()->create([
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $appointment->patient_id,
                'initiated_by' => $request->user()->id,
                'status' => 'queued',
                'progress' => null,
                'analysis_type' => 'visit_brief',
                'selected_report_ids' => $request->reportIds(),
            ]);

            $created->audits()->create([
                'user_id' => $request->user()->id,
                'action' => 'initiated',
                'meta' => ['report_count' => count($request->reportIds())],
            ]);

            return $created;
        });

        if ($started) {
            GenerateAiAnalysis::dispatchSync($analysis->id);
            $analysis->refresh();
        }

        $redirect = $request->input('return_to') === 'visit'
            ? redirect()->route('doctor.appointments.show', $appointment)
            : redirect()->route('doctor.appointments.analysis', [$appointment, $analysis]);

        if ($analysis->status === 'failed') {
            return $redirect;
        }

        $message = match (true) {
            ! $started => 'An analysis is already running for this visit.',
            $analysis->status === 'completed' => 'Analysis ready for review.',
            default => 'Analysis started.',
        };

        return $redirect->with('status', $message);
    }

    public function status(Request $request, Appointment $appointment, AiAnalysis $analysis): JsonResponse
    {
        $this->permit($request, $appointment);
        $this->belongs($appointment, $analysis);

        return response()->json([
            'status' => $analysis->status,
            'step' => $analysis->progress,
            'terminal' => in_array($analysis->status, ['completed', 'failed'], true),
        ]);
    }

    public function update(UpdateAiAnalysisReviewRequest $request, Appointment $appointment, AiAnalysis $analysis): RedirectResponse
    {
        $this->permit($request, $appointment);
        $this->belongs($appointment, $analysis);

        $this->applyChartBrief($analysis, $this->context->preview($appointment));

        $findings = is_array($analysis->structured_output['key_findings'] ?? null)
            ? $analysis->structured_output['key_findings']
            : [];
        $reviewed = array_values(array_unique(array_map('intval', $request->validated('reviewed') ?? [])));
        $reviewed = array_values(array_filter(
            $reviewed,
            fn (int $index) => array_key_exists($index, $findings),
        ));

        $analysis->forceFill([
            'clinician_summary' => $request->validated('clinician_summary'),
            'doctor_notes' => $request->validated('doctor_notes'),
            'reviewed_findings' => $reviewed,
        ])->save();

        $analysis->audits()->create([
            'user_id' => $request->user()->id,
            'action' => 'reviewed',
            'meta' => ['reviewed_count' => count($reviewed)],
        ]);

        return redirect()
            ->route('doctor.appointments.analysis', [$appointment, $analysis])
            ->with('status', 'Review saved. The original analysis was left unchanged.');
    }

    public function approve(Request $request, Appointment $appointment, AiAnalysis $analysis): RedirectResponse
    {
        $this->permit($request, $appointment);
        $this->belongs($appointment, $analysis);
        if ($analysis->status !== 'completed') {
            return redirect()
                ->route('doctor.appointments.analysis', [$appointment, $analysis])
                ->withErrors(['analysis' => 'Generate the analysis again before approving it for the visit note.']);
        }

        $summary = filled($analysis->clinician_summary)
            ? (string) $analysis->clinician_summary
            : (string) $analysis->original_summary;

        $analysis->forceFill([
            'approved_summary' => $summary,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ])->save();

        $analysis->audits()->create([
            'user_id' => $request->user()->id,
            'action' => 'approved',
            'meta' => ['summary' => $summary],
        ]);

        return redirect()
            ->route('doctor.appointments.analysis', [$appointment, $analysis])
            ->with('status', 'Approved summary saved. It was not written into the visit note.');
    }

    private function permit(Request $request, Appointment $appointment): void
    {
        abort_unless(app(AiAnalysisPolicy::class)->manage($request->user(), $appointment), 403);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function applyChartBrief(AiAnalysis $analysis, array $context): void
    {
        if ($analysis->status !== 'failed' || filled(data_get($analysis->structured_output, 'clinical_summary'))) {
            return;
        }

        $mock = ChartAnalysis::output($context, array_map('intval', $analysis->selected_report_ids ?? []));
        $analysis->structured_output = $mock;

        if (blank($analysis->original_summary)) {
            $analysis->original_summary = $mock['clinical_summary'];
        }
    }

    private function belongs(Appointment $appointment, ?AiAnalysis $analysis): void
    {
        if ($analysis) {
            abort_unless((int) $analysis->appointment_id === (int) $appointment->id, 404);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function legacy(?Consultation $consultation): ?array
    {
        $stored = is_array($consultation?->ai_analysis) ? $consultation->ai_analysis : [];
        $gemini = AppointmentPage::geminiAnalysis($stored);

        if ($gemini) {
            $reviews = [];

            foreach ($gemini['reportAnalyses'] as $report) {
                $reading = $report['analysis'];
                $findings = $report['findings'];
                $warnings = $report['warnings'];
                $urls = $report['reportUrls'];

                if ($reading === null && $findings === [] && $warnings === [] && $urls === []) {
                    continue;
                }

                $reviews[] = [
                    'group' => $report['groupName'],
                    'file' => $report['fileName'],
                    'reading' => $reading,
                    'attention' => null,
                    'urls' => $urls,
                    'warnings' => $warnings,
                    'findings' => $findings,
                ];
            }

            return [
                'summary' => $gemini['overallSummary'],
                'picture' => null,
                'history' => null,
                'points' => [],
                'recommendations' => $gemini['doctorRecommendations'],
                'brief' => null,
                'reviews' => $reviews,
            ];
        }

        $summary = filled($consultation?->ai_summary) ? (string) $consultation->ai_summary : null;
        $recommendations = $this->lines($stored['clinical_recommendations'] ?? []);
        $points = $this->lines($stored['points_for_the_doctor'] ?? '');
        $reviews = [];

        foreach (is_array($stored['report_reviews'] ?? null) ? $stored['report_reviews'] : [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $reviews[] = [
                'group' => $this->text($item['group'] ?? null),
                'file' => $this->text($item['file'] ?? null),
                'reading' => $this->text($item['reading'] ?? null),
                'attention' => $this->text($item['attention'] ?? null),
                'urls' => [],
                'warnings' => [],
                'findings' => [],
            ];
        }

        $ready = filled($summary)
            || $reviews !== []
            || $recommendations !== []
            || $points !== []
            || filled($stored['history_relevant_to_this_doctor'] ?? null)
            || filled($stored['clinical_picture'] ?? null)
            || filled($stored['referral_brief'] ?? null);

        if (! $ready) {
            return null;
        }

        return [
            'summary' => $summary,
            'picture' => $this->text($stored['clinical_picture'] ?? null),
            'history' => $this->text($stored['history_relevant_to_this_doctor'] ?? null),
            'points' => $points,
            'recommendations' => $recommendations,
            'brief' => $this->text($stored['referral_brief'] ?? null),
            'reviews' => $reviews,
        ];
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

    private function text(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : $text;
    }
}
