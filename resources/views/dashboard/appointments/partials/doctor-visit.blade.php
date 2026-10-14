@php
use App\Services\AI\MedicalReportExtractor;
use App\Support\AnalysisBrief;
use App\Support\AppointmentPage;
use App\Support\AppointmentPresenter;
use App\Support\MedicalFile;

$notes = $booking['notes'] ?? [];
$consultation = $appointment->consultation;
$canConfirm = $appointment->status === 'pending';
$canCheckIn = $appointment->status === 'confirmed';
$canComplete = in_array($appointment->status, ['confirmed', 'checked_in'], true);
$canCancel = in_array($appointment->status, ['pending', 'confirmed', 'checked_in'], true);
$canSaveConsult = in_array($appointment->status, ['confirmed', 'checked_in', 'completed'], true);
$analyses = $appointment->relationLoaded('analyses') ? $appointment->analyses : collect();
$newestAnalysis = $analyses->first();
$runningAnalysis = $analyses->first(fn ($row) => $row->isRunning());
$failedAnalysis = $newestAnalysis && $newestAnalysis->status === 'failed' ? $newestAnalysis : null;
$completedAnalysis = $analyses->first(function ($row) {
return $row->status === 'completed'
&& is_array($row->structured_output)
&& filled($row->structured_output['clinical_summary'] ?? null);
});
$analysisLabel = match (true) {
$runningAnalysis !== null => 'Processing',
$completedAnalysis !== null => $completedAnalysis->approved_at ? 'Approved' : 'Ready for review',
filled($consultation?->ai_summary) || filled($consultation?->ai_analysis) => 'Earlier brief',
default => 'Not started',
};
$analysisOutput = is_array($completedAnalysis?->structured_output) ? $completedAnalysis->structured_output : [];
$analysisSummary = trim((string) ($analysisOutput['clinical_summary'] ?? ''));
$conditions = collect($analysisOutput['differential_considerations'] ?? [])
->filter(fn ($row) => is_array($row) && filled($row['possibility'] ?? null))
->take(3)
->values();
$highlights = collect(is_array($analysisOutput['highlights'] ?? null) ? $analysisOutput['highlights'] : [])
->filter(fn ($item) => is_scalar($item) && trim((string) $item) !== '')
->map(fn ($item) => trim((string) $item))
->take(4)
->values();
$findings = $analysisOutput === []
? []
: array_slice(AnalysisBrief::present($analysisOutput, [])['findings'] ?? [], 0, 4);
$reviewAnalysis = $runningAnalysis ?? $completedAnalysis ?? $newestAnalysis;
$analysisReports = $appointment->reports->filter(fn ($report) => MedicalReportExtractor::supported($report));
$visitLabel = AppointmentPresenter::VISIT_LABELS[$appointment->visit_type] ?? (filled($appointment->visit_type) ? ucfirst((string) $appointment->visit_type) : 'Consultation');
$headerDate = $appointment->appointment_date ? $appointment->appointment_date->format('l, d M Y') : $booking['date'];
$clock = AppointmentPresenter::time($appointment->appointment_time);
if (filled($appointment->ending_time)) {
$clock .= ' – '.AppointmentPresenter::time($appointment->ending_time);
}
$visitDate = $appointment->appointment_date ? AppointmentPresenter::date($appointment->appointment_date) : $booking['date'];
$chartId = $appointment->patient_id ? '#PT-'.str_pad((string) $appointment->patient_id, 4, '0', STR_PAD_LEFT) : null;
$ageLabel = filled($person['age']) ? $person['age'].' years' : null;

$readNote = function (string $key) use ($booking) {
$record = $booking['health'][$key] ?? [];
$known = filter_var(data_get($record, 'known', true), FILTER_VALIDATE_BOOLEAN);
$value = data_get($record, 'value', '');

if (! $known || $value === null || $value === '') {
return 'Not known';
}

return ucfirst((string) $value);
};

$bpRecord = $booking['health']['bp'] ?? [];
$bpKnown = filter_var(data_get($bpRecord, 'known', true), FILTER_VALIDATE_BOOLEAN);
$bpRaw = $bpKnown ? trim((string) data_get($bpRecord, 'value', '')) : '';
$bpLabel = $bpRaw !== '' ? $bpRaw : 'Not known';
$bpStatus = null;

if ($bpRaw !== '' && preg_match('/(\d{2,3})\s*\/\s*(\d{2,3})/', $bpRaw, $bpMatch)) {
$bpLabel = $bpMatch[1].'/'.$bpMatch[2].' mmHg';
$systolic = (int) $bpMatch[1];
$diastolic = (int) $bpMatch[2];
$bpStatus = ($systolic >= 140 || $diastolic >= 90)
? ['label' => 'High', 'class' => 'bg-red-50 text-[#B91C1C]']
: (($systolic > 120 || $diastolic > 80)
? ['label' => 'Elevated', 'class' => 'bg-[#FFFBEB] text-[#B45309]']
: ['label' => 'Normal', 'class' => 'bg-[#ECFDF3] text-[#15803D]']);
}

$diabeticLabel = $readNote('diabetic');
$thyroidLabel = $readNote('hyperthyroidism');
$allergyRaw = data_get($appointment->patient?->patient_profile?->health_notes, 'allergies');
$allergy = is_array($allergyRaw) ? ($allergyRaw['value'] ?? null) : $allergyRaw;
$allergyLabel = filled($allergy) ? (string) $allergy : 'Not specified';

$chips = [];
if (filled($person['blood_type'])) {
$chips[] = ['label' => $person['blood_type'], 'icon' => 'droplet', 'class' => 'bg-[#FEE2E2] text-[#DC2626]'];
}
if (($bpStatus['label'] ?? null) === 'High') {
$chips[] = ['label' => 'Hypertension', 'icon' => 'heart', 'class' => 'bg-[#FCE7F3] text-[#BE185D]'];
}
if ($thyroidLabel === 'Yes') {
$chips[] = ['label' => 'Hyperthyroidism', 'icon' => 'thyroid', 'class' => 'bg-[#F3E8FF] text-[#7C3AED]'];
}
if ($diabeticLabel === 'Yes') {
$chips[] = ['label' => 'Diabetic', 'icon' => 'droplet', 'class' => 'bg-[#FEE2E2] text-[#DC2626]'];
} elseif ($diabeticLabel === 'No') {
$chips[] = ['label' => 'Non Diabetic', 'icon' => 'leaf', 'class' => 'bg-[#ECFDF3] text-[#15803D]'];
}

$severityFor = function (string $text): ?array {
$lower = strtolower($text);

if (preg_match('/\b(high|severe)\b/', $lower)) {
return ['label' => 'High', 'class' => 'bg-red-50 text-[#B91C1C]'];
}

if (str_contains($lower, 'moderate')) {
return ['label' => 'Moderate', 'class' => 'bg-[#FFF7ED] text-[#C2410C]'];
}

if (str_contains($lower, 'mild')) {
return ['label' => 'Mild', 'class' => 'bg-[#ECFDF3] text-[#15803D]'];
}

return null;
};

$iconFor = function (string $text): array {
$lower = strtolower($text);

if (preg_match('/\b(fever|temperature)\b/', $lower)) {
return ['icon' => 'thermometer', 'class' => 'bg-[#FEE2E2] text-[#DC2626]'];
}

if (preg_match('/\b(headache|migraine|pain)\b/', $lower)) {
return ['icon' => 'bolt', 'class' => 'bg-[#FFF7ED] text-[#EA580C]'];
}

if (preg_match('/\b(weakness|fatigue|tired)\b/', $lower)) {
return ['icon' => 'user', 'class' => 'bg-[#E8F2FF] text-[#0067FF]'];
}

if (preg_match('/\b(throat|cough|cold)\b/', $lower)) {
return ['icon' => 'leaf', 'class' => 'bg-[#ECFDF3] text-[#15803D]'];
}

return ['icon' => 'clipboard', 'class' => 'bg-[#E8F2FF] text-[#0067FF]'];
};

$symptoms = [];
$duration = null;
$reasonLines = preg_split('/\r\n|\r|\n/', (string) ($booking['current_health_issues'] ?? '')) ?: [];

foreach ($reasonLines as $line) {
$line = trim($line);

if ($line === '') {
continue;
}

if (preg_match('/^duration:\s*(.+)$/i', $line, $match)) {
$duration = trim($match[1]);
continue;
}

$symptoms[] = [
'label' => $line,
'detail' => null,
'severity' => $severityFor($line),
'mark' => $iconFor($line),
];
}

if (count($symptoms) === 1 && str_contains($symptoms[0]['label'], ',') && ! str_contains($symptoms[0]['label'], ':')) {
$symptoms = collect(explode(',', $symptoms[0]['label']))
->map(fn ($part) => trim($part))
->filter()
->map(fn ($part) => [
'label' => $part,
'detail' => null,
'severity' => $severityFor($part),
'mark' => $iconFor($part),
])
->values()
->all();
}

if ($duration) {
foreach ($symptoms as $index => $symptom) {
$symptoms[$index]['detail'] = 'Duration: '.$duration;
}
}

$fileSize = function (?int $bytes): ?string {
if ($bytes === null) {
return null;
}

if ($bytes < 1024) {
    return $bytes.' B';
    }

    if ($bytes < 1048576) {
    return number_format($bytes / 1024, $bytes < 10240 ? 1 : 0).' KB';
    }

    return number_format($bytes / 1048576, 1).' MB';
    };

    $findingTone=[ 'orange'=> 'bg-[#FFF7ED] text-[#EA580C]',
    'blue' => 'bg-[#E8F2FF] text-[#0067FF]',
    'green' => 'bg-[#ECFDF3] text-[#15803D]',
    'gray' => 'bg-[#F1F5F9] text-[#64748B]',
    ];

    $initialTab = 'symptoms';
    if ($errors->hasAny(['follow_up_on', 'follow_up_instructions'])) {
    $initialTab = 'follow';
    } elseif ($errors->has('plan')) {
    $initialTab = 'treatment';
    } elseif ($errors->has('diagnosis')) {
    $initialTab = 'diagnosis';
    } elseif ($errors->has('clinical_notes')) {
    $initialTab = 'notes';
    }

    $history = collect($visitHistory ?? [])->map(function ($visit) {
    return [
    'url' => route('doctor.appointments.show', $visit),
    'date' => AppointmentPresenter::date($visit->appointment_date),
    'visit' => AppointmentPresenter::VISIT_LABELS[$visit->visit_type] ?? 'Visit',
    'status' => AppointmentPage::status($visit->status),
    ];
    });
    @endphp

    <div x-data="{ tab: @js($initialTab), menu: false, editing: @js($errors->hasAny(['clinical_notes', 'diagnosis', 'plan', 'follow_up_on', 'follow_up_instructions'])), analyzing: false }" @keydown.escape.window="menu = false">
        <header class="mb-4">
            <a href="{{ $backUrl }}" class="inline-flex items-center gap-1 text-sm font-medium text-[#64748B] no-underline hover:text-[#181A1E]">
                <x-ui.icon name="chevron" class="h-4 w-4 rotate-90" />
                {{ $backLabel }}
            </a>
            <div class="mt-3 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-semibold tracking-tight text-[#181A1E] sm:text-[22px]">Appointment Details</h1>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $booking['status']['class'] }}">{{ $booking['status']['label'] }}</span>
                    </div>
                    <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-[#64748B]">
                        <span class="inline-flex items-center gap-1.5">
                            <x-ui.icon name="calendar" class="h-4 w-4" />
                            {{ $headerDate }}
                        </span>
                        <span aria-hidden="true">·</span>
                        <span class="inline-flex items-center gap-1.5">
                            <x-ui.icon name="clock" class="h-4 w-4" />
                            {{ $clock }}
                        </span>
                        <span aria-hidden="true">·</span>
                        <span>{{ $visitLabel }}</span>
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <form method="POST" action="{{ route('doctor.appointments.analysis.store', $appointment) }}" @submit="analyzing = true">
                        @csrf
                        <input type="hidden" name="return_to" value="visit">
                        @foreach ($analysisReports as $report)
                        <input type="hidden" name="report_ids[]" value="{{ $report->id }}">
                        @endforeach
                        <button type="submit" class="btn-secondary h-10" :disabled="analyzing || @js($runningAnalysis !== null)">
                            <x-ui.icon name="spark" class="h-4 w-4 text-[#0067FF]" />
                            <span x-show="!analyzing">{{ $runningAnalysis ? 'Analyzing' : 'AI Re-analyze' }}</span>
                            <span x-show="analyzing" x-cloak>Analyzing…</span>
                        </button>
                    </form>
                    <a href="{{ route('doctor.patients.show', $appointment->patient_id) }}" class="btn-secondary h-10">Open Patient Chart</a>
                    @if ($canCheckIn)
                    <form method="POST" action="{{ route('doctor.appointments.update-status', $appointment) }}">
                        @csrf
                        <input type="hidden" name="status" value="checked_in">
                        <button type="submit" class="btn-secondary h-10">
                            <x-ui.icon name="check" class="h-4 w-4" />
                            Check In
                        </button>
                    </form>
                    @endif
                    @if ($canConfirm)
                    <form method="POST" action="{{ route('doctor.appointments.update-status', $appointment) }}">
                        @csrf
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit" class="btn-primary h-10">Approve &amp; Confirm</button>
                    </form>
                    @endif
                    <button type="button" class="{{ $canConfirm ? 'btn-secondary' : 'btn-primary' }} h-10" @click="tab = 'treatment'; editing = true; document.getElementById('consult')?.scrollIntoView({ behavior: 'smooth', block: 'start' })">
                        <x-ui.icon name="pill" class="h-4 w-4" />
                        Start Consultation
                    </button>
                    <div class="relative">
                        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-[#E2E8F0] bg-white text-[#64748B] hover:bg-[#F8FAFC]" @click="menu = !menu" aria-label="More actions" :aria-expanded="menu">
                            <x-ui.icon name="dots" class="h-4 w-4" />
                        </button>
                        <div x-show="menu" x-cloak @click.outside="menu = false" class="absolute right-0 z-20 mt-1 w-64 rounded-xl border border-[#E7EEF6] bg-white p-2 shadow-lift">
                            @if ($canComplete)
                            <form method="POST" action="{{ route('doctor.appointments.update-status', $appointment) }}">
                                @csrf
                                <input type="hidden" name="status" value="completed">
                                <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-[#181A1E] hover:bg-[#F8FAFC]">Complete visit</button>
                            </form>
                            <form method="POST" action="{{ route('doctor.appointments.update-status', $appointment) }}">
                                @csrf
                                <input type="hidden" name="status" value="no_show">
                                <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-[#181A1E] hover:bg-[#F8FAFC]">Mark no-show</button>
                            </form>
                            @endif
                            @if ($canCancel)
                            <form method="POST" action="{{ route('doctor.appointments.update-status', $appointment) }}" class="border-t border-[#F1F5F9] px-3 py-2">
                                @csrf
                                <input type="hidden" name="status" value="cancelled">
                                <label class="block text-xs font-semibold text-[#64748B]">
                                    Cancellation reason
                                    <input type="text" name="cancel_reason" required maxlength="255" class="field mt-1 h-9">
                                </label>
                                <button type="submit" class="mt-2 text-sm font-semibold text-[#DC2626]">Cancel appointment</button>
                            </form>
                            @endif
                            <!-- <a href="#messages" class="block rounded-lg px-3 py-2 text-sm font-medium text-[#181A1E] no-underline hover:bg-[#F8FAFC]" @click="menu = false">Messages</a> -->
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="grid gap-4 xl:grid-cols-2">
            <div class="flex min-w-0 flex-col gap-4">
                <section class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-card">
                    <h2 class="sr-only">Patient Information</h2>
                    <div class="flex items-start gap-4">
                        <x-ui.avatar :name="$person['name'] ?: 'Patient'" :photo="$person['photo']" size="3xl" tone="violet" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-lg font-semibold text-[#181A1E]">{{ $person['name'] ?: 'Not provided' }}</p>
                                    @if (filled($person['origin'] ?? null))
                                    <p class="mt-1 text-sm font-semibold text-[#0067FF]">{{ $person['origin'] }}</p>
                                    @endif
                                    @if ($chartId)
                                    <p class="text-sm text-[#64748B]">ID: {{ $chartId }}</p>
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-center gap-3 text-sm text-[#64748B]">
                                    @if ($ageLabel)
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-ui.icon name="user" class="h-4 w-4" />
                                        {{ $ageLabel }}
                                    </span>
                                    @endif
                                    @if (filled($person['gender']))
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-ui.icon name="gender" class="h-4 w-4" />
                                        {{ $person['gender'] }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-[#64748B]">
                                @if (filled($person['phone']))
                                <span class="inline-flex items-center gap-1.5">
                                    <x-ui.icon name="phone" class="h-4 w-4" />
                                    {{ $person['phone'] }}
                                </span>
                                @endif
                                @if (filled($person['email']))
                                <a href="mailto:{{ $person['email'] }}" class="inline-flex min-w-0 items-center gap-1.5 text-[#64748B] no-underline hover:text-[#0067FF]">
                                    <x-ui.icon name="mail" class="h-4 w-4 shrink-0" />
                                    <span class="truncate">{{ $person['email'] }}</span>
                                </a>
                                @endif
                            </div>
                            @if ($chips !== [])
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach ($chips as $chip)
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $chip['class'] }}">
                                    <x-ui.icon :name="$chip['icon']" class="h-3.5 w-3.5" />
                                    {{ $chip['label'] }}
                                </span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="flex-1 rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-card">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-sm font-semibold text-[#181A1E]">Visit Information</h2>
                        <!-- <button type="button" class="inline-flex items-center gap-1 text-sm font-semibold text-[#0067FF]" @click="editing = true; tab = 'notes'; document.getElementById('consult')?.scrollIntoView({ behavior: 'smooth', block: 'start' })">
                            <x-ui.icon name="pencil" class="h-3.5 w-3.5" />
                            Edit
                        </button> -->
                    </div>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach ([
                        ['user', 'Doctor', $booking['doctor_name'] ?: 'Not provided'],
                        ['stethoscope', 'Specialization', $booking['specialization'] ?: 'Not provided'],
                        ['calendar', 'Date', $visitDate ?: 'Not provided'],
                        ['grid', 'Day', $booking['day'] ?: 'Not provided'],
                        ['clock', 'Time slot', $clock ?: 'Not provided'],
                        ['check', 'Status', null],
                        ['stethoscope', 'Consultation type', $visitLabel],
                        ['card', 'Ticket price', $booking['ticket_price'] ? '$'.$booking['ticket_price'] : 'Not provided'],
                        ] as [$icon, $label, $value])
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#F4F7FB] text-[#64748B]">
                                <x-ui.icon :name="$icon" class="h-4 w-4" />
                            </span>
                            <div class="min-w-0">
                                <dt class="text-xs text-[#64748B]">{{ $label }}</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-[#181A1E]">
                                    @if ($label === 'Status')
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $booking['status']['class'] }}">{{ $booking['status']['label'] }}</span>
                                    @else
                                    {{ $value }}
                                    @endif
                                </dd>
                            </div>
                        </div>
                        @endforeach
                        @if ($booking['payment']['label'])
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#F4F7FB] text-[#64748B]">
                                <x-ui.icon name="card" class="h-4 w-4" />
                            </span>
                            <div>
                                <dt class="text-xs text-[#64748B]">Payment</dt>
                                <dd class="mt-0.5 text-sm font-semibold text-[#181A1E]">{{ $booking['payment']['label'] }}</dd>
                            </div>
                        </div>
                        @endif
                    </dl>
                </section>
            </div>

            <div class="flex min-w-0 flex-col gap-4">
                <section class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-card">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <x-ui.icon name="spark" class="h-4 w-4 text-[#0067FF]" />
                            <h2 class="text-sm font-semibold text-[#181A1E]">AI Analysis</h2>
                            <span class="rounded bg-[#E8F2FF] px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[#0067FF]">Beta</span>
                            <span class="text-xs font-semibold text-[#64748B]">{{ $analysisLabel }}</span>
                        </div>
                        <a href="{{ $reviewAnalysis ? route('doctor.appointments.analysis', [$appointment, $reviewAnalysis]) : route('doctor.appointments.analysis', $appointment) }}" class="shrink-0 text-sm font-semibold text-[#0067FF] no-underline hover:underline">View Full Analysis</a>
                    </div>
                    @if ($failedAnalysis)
                    <p class="mt-3 rounded-lg border border-[#FECACA] bg-[#FEF2F2] px-3 py-2 text-sm text-[#B91C1C]">{{ session('analysis_error', $failedAnalysis->safeError()) }}</p>
                    @endif
                    <p x-show="analyzing" x-cloak class="mt-3 text-sm text-[#0067FF]">Re-analyzing this visit. This can take a minute.</p>
                    @if ($analysisSummary !== '')
                    <p class="mt-3 text-sm leading-6 text-[#4E545F]">{{ $analysisSummary }}</p>
                    @endif
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <h3 class="text-xs font-semibold text-[#64748B]">{{ $conditions->isNotEmpty() ? 'Possible Conditions' : 'Highlights' }}</h3>
                            @if ($conditions->isNotEmpty())
                            <ol class="mt-3 space-y-3">
                                @foreach ($conditions as $condition)
                                <li class="flex items-start gap-2">
                                    <span class="w-4 shrink-0 text-sm text-[#64748B]">{{ $loop->iteration }}</span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-[#181A1E]">{{ $condition['possibility'] }}</p>
                                        @if (filled($condition['supporting_evidence'] ?? null))
                                        <p class="mt-0.5 text-xs leading-5 text-[#64748B]">{{ $condition['supporting_evidence'] }}</p>
                                        @endif
                                    </div>
                                </li>
                                @endforeach
                            </ol>
                            @elseif ($highlights->isNotEmpty())
                            <ul class="mt-3 space-y-2">
                                @foreach ($highlights as $highlight)
                                <li class="text-sm font-medium text-[#181A1E]">{{ $highlight }}</li>
                                @endforeach
                            </ul>
                            @else
                            <p class="mt-3 text-sm text-[#64748B]">{{ $completedAnalysis ? 'No separate conditions were suggested.' : 'Possible conditions appear here after an analysis is ready.' }}</p>
                            @endif
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold text-[#64748B]">Key Findings</h3>
                            @if ($findings === [])
                            <p class="mt-3 text-sm text-[#64748B]">{{ $completedAnalysis ? 'No findings were returned.' : 'Key findings appear here after an analysis is ready.' }}</p>
                            @else
                            <ul class="mt-3 space-y-3">
                                @foreach ($findings as $finding)
                                <li class="flex items-start gap-2">
                                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $findingTone[$finding['tone']] ?? $findingTone['gray'] }}">
                                        <x-ui.icon :name="$finding['icon']" class="h-3.5 w-3.5" />
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-medium text-[#181A1E]">{{ $finding['title'] }}</span>
                                        @if (($finding['line'] ?? '') !== '')
                                        <span class="block text-xs leading-5 text-[#64748B]">{{ $finding['line'] }}</span>
                                        @endif
                                    </span>
                                </li>
                                @endforeach
                            </ul>
                            @endif
                        </div>
                    </div>
                    <p class="mt-4 flex items-start gap-2 rounded-lg bg-[#FFFBEB] px-3 py-2 text-xs leading-5 text-[#92400E]">
                        <x-ui.icon name="alert" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                        <span>This is an AI-assisted analysis based on uploaded reports and symptoms. Please review and confirm clinically.</span>
                    </p>
                </section>

                <section id="consult" class="flex min-h-0 flex-1 scroll-mt-4 flex-col rounded-xl border border-[#E2E8F0] bg-white shadow-card">
                    <div class="grid grid-cols-5 border-b border-[#E2E8F0]" role="tablist" aria-label="Consultation">
                        @foreach ([
                        'symptoms' => 'Symptoms & Examination',
                        'diagnosis' => 'Diagnosis',
                        'treatment' => 'Treatment Plan',
                        'follow' => 'Follow-up',
                        'notes' => 'Doctor Notes',
                        ] as $key => $label)
                        <button
                            type="button"
                            role="tab"
                            @click="tab = '{{ $key }}'"
                            :aria-selected="tab === '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'border-[#0067FF] text-[#0067FF]' : 'border-transparent text-[#64748B] hover:text-[#181A1E]'"
                            class="flex h-full min-w-0 items-center justify-center border-b-2 px-1.5 py-3 text-center text-xs font-semibold leading-tight sm:text-[13px]">{{ $label }}</button>
                        @endforeach
                    </div>

                    <form method="POST" action="{{ route('doctor.appointments.consult.update', $appointment) }}" class="flex flex-1 flex-col p-5">
                        @csrf
                        @method('PUT')

                        <div x-show="tab === 'symptoms'" class="grid gap-6 lg:grid-cols-2">
                            <div>
                                <h3 class="text-sm font-semibold text-[#181A1E]">Symptoms (from patient)</h3>
                                @if ($symptoms === [])
                                <p class="mt-4 text-sm text-[#64748B]">No symptoms were recorded for this visit.</p>
                                @else
                                <ul class="mt-3 space-y-4">
                                    @foreach ($symptoms as $symptom)
                                    <li class="flex items-start justify-between gap-3">
                                        <div class="flex min-w-0 items-start gap-3">
                                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full {{ $symptom['mark']['class'] }}">
                                                <x-ui.icon :name="$symptom['mark']['icon']" class="h-4 w-4" />
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block text-sm font-medium text-[#181A1E]">{{ $symptom['label'] }}</span>
                                                @if ($symptom['detail'])
                                                <span class="block text-xs text-[#64748B]">{{ $symptom['detail'] }}</span>
                                                @endif
                                            </span>
                                        </div>
                                        @if ($symptom['severity'])
                                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold {{ $symptom['severity']['class'] }}">{{ $symptom['severity']['label'] }}</span>
                                        @endif
                                    </li>
                                    @endforeach
                                </ul>
                                @endif
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-[#181A1E]">Examination Findings</h3>
                                <p class="sr-only">Health notes</p>
                                <ul class="mt-3 space-y-4">
                                    <li class="flex items-start justify-between gap-3">
                                        <div class="flex items-start gap-3">
                                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#FEE2E2] text-[#DC2626]">
                                                <x-ui.icon name="activity" class="h-4 w-4" />
                                            </span>
                                            <span>
                                                <span class="block text-xs text-[#64748B]">Blood Pressure</span>
                                                <span class="block text-sm font-semibold text-[#181A1E]">{{ $bpLabel }}</span>
                                            </span>
                                        </div>
                                        @if ($bpStatus)
                                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold {{ $bpStatus['class'] }}">{{ $bpStatus['label'] }}</span>
                                        @endif
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#F1F5F9] text-[#64748B]">
                                            <x-ui.icon name="droplet" class="h-4 w-4" />
                                        </span>
                                        <span>
                                            <span class="block text-xs text-[#64748B]">Diabetic</span>
                                            <span class="block text-sm font-semibold {{ in_array($diabeticLabel, ['Yes', 'No'], true) ? 'text-[#DC2626]' : 'text-[#64748B]' }}">{{ $diabeticLabel }}</span>
                                        </span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#FCE7F3] text-[#DB2777]">
                                            <x-ui.icon name="thyroid" class="h-4 w-4" />
                                        </span>
                                        <span>
                                            <span class="block text-xs text-[#64748B]">Hyperthyroidism</span>
                                            <span class="block text-sm font-semibold {{ in_array($thyroidLabel, ['Yes', 'No'], true) ? 'text-[#DC2626]' : 'text-[#64748B]' }}">{{ $thyroidLabel }}</span>
                                        </span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#F1F5F9] text-[#64748B]">
                                            <x-ui.icon name="clipboard" class="h-4 w-4" />
                                        </span>
                                        <span>
                                            <span class="block text-xs text-[#64748B]">Allergies</span>
                                            <span class="block text-sm font-semibold {{ $allergyLabel === 'Not specified' ? 'text-[#94A3B8]' : 'text-[#181A1E]' }}">{{ $allergyLabel }}</span>
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div x-show="tab === 'diagnosis'" x-cloak>
                            <label class="block text-sm font-semibold text-[#181A1E]">
                                Diagnosis
                                @if ($canSaveConsult)
                                <input type="text" name="diagnosis" value="{{ old('diagnosis', $consultation?->diagnosis) }}" placeholder="Record the diagnosis" class="field mt-2">
                                @else
                                <p class="mt-3 whitespace-pre-line font-normal leading-6 text-[#181A1E]">{{ filled($consultation?->diagnosis) ? $consultation->diagnosis : 'Not recorded yet.' }}</p>
                                @endif
                            </label>
                        </div>

                        <div x-show="tab === 'treatment'" x-cloak id="treatment">
                            <label class="block text-sm font-semibold text-[#181A1E]">
                                Medicines and plan
                                @if ($canSaveConsult)
                                <textarea name="plan" rows="5" placeholder="Write the prescription and treatment plan" class="field mt-2">{{ old('plan', $consultation?->plan) }}</textarea>
                                @else
                                <p class="mt-3 whitespace-pre-line font-normal leading-6 text-[#181A1E]">{{ filled($consultation?->plan) ? $consultation->plan : 'Not recorded yet.' }}</p>
                                @endif
                            </label>
                        </div>

                        <div x-show="tab === 'follow'" x-cloak id="follow-up">
                            @if ($canSaveConsult)
                            <div class="space-y-4">
                                <label class="block text-sm font-semibold text-[#181A1E]">
                                    Follow-up instructions
                                    <textarea name="follow_up_instructions" rows="4" placeholder="What the patient should do next" class="field mt-2">{{ old('follow_up_instructions', $consultation?->follow_up_instructions) }}</textarea>
                                </label>
                                <label class="block text-sm font-semibold text-[#181A1E]">
                                    Next review date
                                    <input type="date" name="follow_up_on" value="{{ old('follow_up_on', $consultation?->follow_up_on?->toDateString()) }}" class="field mt-2">
                                </label>
                            </div>
                            @else
                            <h3 class="text-sm font-semibold text-[#181A1E]">Follow-up</h3>
                            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-[#181A1E]">{{ filled($consultation?->follow_up_instructions) ? $consultation->follow_up_instructions : 'Not recorded yet.' }}</p>
                            @if ($consultation?->follow_up_on)
                            <p class="mt-2 text-sm text-[#64748B]">Next review: {{ AppointmentPresenter::date($consultation->follow_up_on) }}</p>
                            @endif
                            @endif
                        </div>

                        <div x-show="tab === 'notes'" x-cloak>
                            <label class="block text-sm font-semibold text-[#181A1E]">
                                Clinical notes
                                @if (count($notes) === 0)
                                <p class="mt-3 text-sm font-normal leading-6 text-[#64748B]">No notes from the doctor yet.</p>
                                @endif
                                @if ($canSaveConsult)
                                <textarea name="clinical_notes" rows="5" placeholder="Notes from this consultation" class="field mt-2">{{ old('clinical_notes', $consultation?->clinical_notes) }}</textarea>
                                @elseif (count($notes) > 0)
                                <div class="mt-3 space-y-4 font-normal">
                                    @foreach ($notes as $note)
                                    <div>
                                        <p class="whitespace-pre-line text-sm leading-6 text-[#181A1E]">{{ $note['text'] }}</p>
                                        @if ($note['date'] !== '')
                                        <p class="mt-1 text-xs text-[#64748B]">{{ $note['date'] }}</p>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </label>
                        </div>

                        @unless ($canSaveConsult)
                        <p class="mt-4 text-sm text-[#64748B]">Confirm the visit before saving consultation notes.</p>
                        @else
                        <div class="mt-auto flex justify-end pt-4">
                            <button type="submit" class="btn-primary">Save consultation</button>
                        </div>
                        @endunless
                    </form>
                </section>
            </div>
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-3">
            <section class="h-full rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="inline-flex items-center gap-2 text-sm font-semibold text-[#181A1E]">
                        <x-ui.icon name="file" class="h-4 w-4 text-[#0067FF]" />
                        Uploaded Reports
                    </h2>
                </div>
                @if ($appointment->reports->isEmpty())
                <p class="mt-4 text-sm leading-6 text-[#64748B]">No reports were attached to this booking.</p>
                @else
                <ul class="mt-3 divide-y divide-[#F1F5F9]">
                    @foreach ($appointment->reports as $file)
                    @php
                    $extension = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));
                    $kind = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? 'Image' : ($file->group_name ?: 'File');
                    $size = $fileSize(MedicalFile::size((string) $file->path));
                    $meta = $kind.($size ? ' · '.$size : '');
                    $uploaded = $file->created_at ? AppointmentPresenter::date($file->created_at) : $visitDate;
                    @endphp
                    <li class="flex items-center gap-3 py-3">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#F4F7FB] text-[#64748B]">
                            <x-ui.icon name="file" class="h-4 w-4" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-[#181A1E]">{{ $file->original_name }}</span>
                            <span class="block text-xs text-[#64748B]">{{ $meta }}</span>
                        </span>
                        <span class="hidden text-xs text-[#64748B] sm:block">{{ $uploaded }}</span>
                        <a href="{{ route('doctor.appointments.reports.show', [$appointment, $file]) }}" target="_blank" rel="noopener" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[#0067FF] hover:bg-[#F4F7FB]" aria-label="Download {{ $file->original_name }}">
                            <x-ui.icon name="download" class="h-4 w-4" />
                        </a>
                    </li>
                    @endforeach
                </ul>
                @foreach ($appointment->reports->pluck('group_name')->unique() as $groupName)
                <span class="sr-only">{{ $groupName }}</span>
                @endforeach
                @endif
            </section>

            <section class="h-full rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="inline-flex items-center gap-2 text-sm font-semibold text-[#181A1E]">
                        <x-ui.icon name="calendar" class="h-4 w-4 text-[#0067FF]" />
                        Recent Visit History
                    </h2>
                    <a href="{{ route('doctor.patients.show', $appointment->patient_id) }}" class="text-sm font-semibold text-[#0067FF] no-underline hover:underline">View All</a>
                </div>
                @if ($history->isEmpty())
                <p class="mt-4 text-sm text-[#64748B]">No other visits with you yet.</p>
                @else
                <ul class="mt-3 space-y-3">
                    @foreach ($history as $visit)
                    <li class="flex items-center gap-3">
                        <span class="h-2 w-2 shrink-0 rounded-full bg-[#0067FF]" aria-hidden="true"></span>
                        <a href="{{ $visit['url'] }}" class="w-[108px] shrink-0 text-sm font-semibold text-[#181A1E] no-underline hover:text-[#0067FF]">{{ $visit['date'] }}</a>
                        <span class="min-w-0 flex-1 truncate text-sm text-[#4E545F]">{{ $visit['visit'] }}</span>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold {{ $visit['status']['class'] }}">{{ $visit['status']['label'] }}</span>
                    </li>
                    @endforeach
                </ul>
                @endif
            </section>

            <section class="h-full rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-card">
                <h2 class="inline-flex items-center gap-2 text-sm font-semibold text-[#181A1E]">
                    <x-ui.icon name="bolt" class="h-4 w-4 text-[#0067FF]" />
                    Quick Actions
                </h2>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <button type="button" class="flex flex-col items-center justify-center gap-2 rounded-xl border border-[#E2E8F0] bg-white px-3 py-4 text-sm font-semibold text-[#181A1E] hover:border-[#0067FF]/40 hover:bg-[#F7FAFF]" @click="tab = 'notes'; editing = true; document.getElementById('consult')?.scrollIntoView({ behavior: 'smooth', block: 'start' })">
                        <x-ui.icon name="note" class="h-5 w-5 text-[#0067FF]" />
                        Add Note
                    </button>
                    <button type="button" class="flex flex-col items-center justify-center gap-2 rounded-xl border border-[#E2E8F0] bg-white px-3 py-4 text-sm font-semibold text-[#181A1E] hover:border-[#0067FF]/40 hover:bg-[#F7FAFF]" @click="tab = 'treatment'; editing = true; document.getElementById('consult')?.scrollIntoView({ behavior: 'smooth', block: 'start' })">
                        <x-ui.icon name="pill" class="h-5 w-5 text-[#0067FF]" />
                        Prescribe Medicine
                    </button>
                    <a href="#refer" class="flex flex-col items-center justify-center gap-2 rounded-xl border border-[#E2E8F0] bg-white px-3 py-4 text-sm font-semibold text-[#181A1E] no-underline hover:border-[#0067FF]/40 hover:bg-[#F7FAFF]">
                        <x-ui.icon name="users" class="h-5 w-5 text-[#0067FF]" />
                        Send Referral
                    </a>
                    <button type="button" class="flex flex-col items-center justify-center gap-2 rounded-xl border border-[#E2E8F0] bg-white px-3 py-4 text-sm font-semibold text-[#181A1E] hover:border-[#0067FF]/40 hover:bg-[#F7FAFF]" @click="tab = 'follow'; editing = true; document.getElementById('consult')?.scrollIntoView({ behavior: 'smooth', block: 'start' })">
                        <x-ui.icon name="calendar" class="h-5 w-5 text-[#0067FF]" />
                        Schedule Follow-up
                    </button>
                </div>
            </section>
        </div>

        <!-- <div class="mt-4">
        @include('dashboard.appointments.partials.visit-messages')
    </div> -->

        <div class="mt-4">
            @include('dashboard.appointments.partials.referral-form')
        </div>
    </div>