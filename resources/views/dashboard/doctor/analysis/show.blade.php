@extends('layouts.dashboard')

@php
    $patientName = $context['patient']['name'] ?: 'Patient';
    $open = $analysis && $analysis->isRunning();
    $completed = $analysis && $analysis->status === 'completed';
    $failed = $analysis && $analysis->status === 'failed';
    $briefReady = $completed || $failed;
    $output = is_array($analysis?->structured_output) ? $analysis->structured_output : [];
    $findings = $output['key_findings'] ?? [];
    $reviewed = array_map('intval', $analysis->reviewed_findings ?? []);
    $running = $analyses->first(fn ($item) => $item->isRunning());
    $brief = \App\Support\AnalysisBrief::present($output, $context);
    $gender = filled($context['patient']['gender'] ?? null) ? ucfirst((string) $context['patient']['gender']) : null;
    $meta = array_filter([
        $context['patient']['age'] ? $context['patient']['age'].' years' : null,
        $gender,
        filled($context['appointment']['date'] ?? null) ? 'Appointment: '.$context['appointment']['date'] : null,
    ]);
@endphp

@section('title', 'AI Clinical Analysis')

@section('content')
    <div
        @if ($open)
            data-poll-url="{{ route('doctor.appointments.analysis.status', [$appointment, $analysis]) }}"
            data-poll-status="{{ $analysis->status }}"
            data-poll-step="{{ $analysis->progress }}"
        @endif
        x-data="{
            analyzing: false,
            step: 0,
            steps: ['Reading the chart', 'Reviewing reports', 'Writing the brief', 'Preparing the review'],
            begin() {
                this.analyzing = true;
                this.timer = window.setInterval(() => {
                    if (this.step < this.steps.length - 1) {
                        this.step++;
                    }
                }, 2500);
            },
            start() {
                if (!this.$el.dataset.pollUrl) {
                    return;
                }

                this.timer = window.setInterval(() => this.tick(), 3000);
            },
            async tick() {
                try {
                    const response = await fetch(this.$el.dataset.pollUrl, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();

                    if (data.terminal || data.status !== this.$el.dataset.pollStatus || data.step !== this.$el.dataset.pollStep) {
                        window.location.reload();
                    }
                } catch {
                    // The next poll retries.
                }
            },
        }"
        x-init="start()"
    >
        <div x-show="analyzing" x-cloak class="mx-auto w-full max-w-lg pt-4">
            <section class="overflow-hidden rounded-2xl border border-[#E2E8F0] bg-white shadow-card" aria-live="polite">
                <div class="bg-gradient-to-br from-[#F4F8FF] to-white px-6 pb-5 pt-6">
                    <div class="flex items-center gap-3">
                        <x-ui.avatar :name="$patientName" :photo="$appointment->patient?->photo" size="lg" />
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-[#0067FF]">AI Clinical Analysis</p>
                            <h1 class="mt-0.5 truncate text-lg font-semibold tracking-tight text-[#181A1E]">{{ $patientName }}</h1>
                            <p class="mt-0.5 truncate text-sm text-[#64748B]">
                                {{ $context['appointment']['date'] ?: 'Date not recorded' }}
                                @if (filled($context['appointment']['reason'] ?? null))
                                    · {{ $context['appointment']['reason'] }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                <div class="px-6 pb-6">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold text-[#181A1E]">Preparing the brief</h2>
                            <p class="mt-1 text-sm text-[#64748B]">Reviewing the chart and selected reports.</p>
                        </div>
                        <p class="shrink-0 text-sm font-semibold text-[#0067FF]" x-text="`${step + 1} of ${steps.length}`"></p>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-[#E8F2FF]">
                        <div class="h-full rounded-full bg-[#0067FF] transition-all duration-500" :style="`width: ${((step + 1) / steps.length) * 100}%`"></div>
                    </div>
                    <ol class="mt-6">
                        <template x-for="(label, index) in steps" :key="label">
                            <li class="relative flex gap-3" :class="index === steps.length - 1 ? '' : 'pb-5'">
                                <span x-show="index !== steps.length - 1" class="absolute left-4 top-8 h-[calc(100%-1.25rem)] w-px bg-[#E2E8F0]" aria-hidden="true"></span>
                                <span
                                    class="relative z-10 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                                    :class="index < step ? 'bg-[#ECFDF3] text-[#15803D]' : (index === step ? 'bg-[#0067FF] text-white ring-4 ring-[#E8F2FF]' : 'bg-[#F8FAFC] text-[#94A3B8]')"
                                >
                                    <span x-show="index < step">✓</span>
                                    <span x-show="index >= step" x-text="index + 1"></span>
                                    <span x-show="index === step" class="absolute inset-0 animate-ping rounded-full bg-[#0067FF]/25" aria-hidden="true"></span>
                                </span>
                                <div class="min-w-0 pt-1">
                                    <p class="text-sm" :class="index === step ? 'font-semibold text-[#181A1E]' : (index < step ? 'font-medium text-[#181A1E]' : 'text-[#94A3B8]')" x-text="label"></p>
                                    <p x-show="index < step" class="text-xs text-[#15803D]">Done</p>
                                    <p x-show="index === step" class="text-xs font-medium text-[#0067FF]">In progress</p>
                                </div>
                            </li>
                        </template>
                    </ol>
                    <p class="mt-6 border-t border-[#E2E8F0] pt-4 text-xs leading-5 text-[#64748B]">The brief appears here when it is ready. Nothing is written into the visit note.</p>
                </div>
            </section>
        </div>

        <div x-show="!analyzing">
            @if ($open)
                <div class="mx-auto w-full max-w-lg pt-4">
                    @include('dashboard.doctor.analysis.partials.progress')
                    <p class="mt-4 text-center">
                        <a href="{{ $backUrl }}" class="text-sm font-semibold text-[#0067FF] no-underline">Back to appointment</a>
                    </p>
                </div>
            @else
            <header class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex min-w-0 items-start gap-3">
                    <x-ui.avatar :name="$patientName" :photo="$appointment->patient?->photo" size="xl" />
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-[#0067FF]">AI Clinical Analysis</p>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <h1 class="truncate text-xl font-semibold tracking-tight text-[#181A1E]">{{ $patientName }}</h1>
                            @if ($briefReady)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#ECFDF3] px-2.5 py-1 text-xs font-semibold text-[#15803D]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#16A34A]"></span>
                                    {{ $analysis->approved_at ? 'Approved' : 'Ready for review' }}
                                </span>
                            @elseif ($open)
                                <span class="inline-flex items-center rounded-full bg-[#E8F2FF] px-2.5 py-1 text-xs font-semibold text-[#0067FF]">Processing</span>
                            @endif
                        </div>
                        @if (count($meta) > 0)
                            <p class="mt-1 text-sm text-[#4E545F]">{{ implode(' · ', $meta) }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ $backUrl }}" class="btn-secondary">
                        <x-ui.icon name="chevron-left" class="h-4 w-4" />
                        Back to appointment
                    </a>
                    @if (count($context['reports']) > 0)
                        <a href="{{ $context['reports'][0]['url'] }}" target="_blank" rel="noopener" class="btn-secondary">
                            <x-ui.icon name="file" class="h-4 w-4" />
                            Source documents
                        </a>
                    @endif
                </div>
            </header>

            <p class="mb-4 flex items-center gap-2 text-xs text-[#64748B]">
                <x-ui.icon name="info" class="h-4 w-4 shrink-0 text-[#0067FF]" />
                Decision support only. Review findings before adding anything to the visit note.
            </p>

            @if (session('status') && ! str_contains((string) session('status'), 'did not return a result'))
                <p class="mb-4 rounded-lg border border-[#BBF7D0] bg-[#F0FDF4] px-3 py-2 text-sm text-[#15803D]">{{ session('status') }}</p>
            @endif

            @if ($errors->any())
                <p class="mb-4 rounded-lg border border-[#FECACA] bg-[#FEF2F2] px-3 py-2 text-sm text-[#B91C1C]">{{ $errors->first() }}</p>
            @endif

            <div class="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <div class="min-w-0 space-y-4">
                    @if ($briefReady)
                        @include('dashboard.doctor.analysis.partials.results')
                    @endif

                    @if ($legacy && ! $completed && ! $failed)
                        @include('dashboard.doctor.analysis.partials.legacy')
                    @elseif ($legacy)
                        <details class="rounded-xl border border-[#E2E8F0] bg-white px-4 py-3 shadow-card">
                            <summary class="cursor-pointer text-sm font-semibold text-[#181A1E]">Earlier visit brief</summary>
                            <div class="mt-3">
                                @include('dashboard.doctor.analysis.partials.legacy')
                            </div>
                        </details>
                    @endif

                    @if (! $open)
                        <details class="rounded-xl border border-[#E2E8F0] bg-white shadow-card" @if (request()->boolean('regenerate') || (! $completed && ! $failed && ! $legacy)) open @endif>
                            <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-[#181A1E]">{{ $completed || $failed ? 'Generate again' : 'Review information before analysis' }}</summary>
                            <form method="POST" action="{{ route('doctor.appointments.analysis.store', $appointment) }}" class="space-y-4 border-t border-[#E2E8F0] px-4 py-4" @submit="begin()">
                                @csrf
                                @include('dashboard.doctor.analysis.partials.context', ['selectable' => true])
                                <button type="submit" class="btn-primary">Generate AI Analysis</button>
                            </form>
                        </details>
                    @else
                        <details class="rounded-xl border border-[#E2E8F0] bg-white shadow-card">
                            <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-[#181A1E]">Information selected for this analysis</summary>
                            <div class="space-y-4 border-t border-[#E2E8F0] px-4 py-4">
                                @include('dashboard.doctor.analysis.partials.context', ['selectable' => false])
                            </div>
                        </details>
                    @endif
                </div>

                <aside class="min-w-0 space-y-4 lg:sticky lg:top-4">
                    @if ($running && (! $analysis || $running->id !== $analysis->id))
                        <a href="{{ route('doctor.appointments.analysis', [$appointment, $running]) }}" class="block rounded-xl border border-[#E2E8F0] bg-white px-4 py-3 text-sm font-semibold text-[#0067FF] no-underline shadow-card">An analysis is still running</a>
                    @endif

                    @if ($briefReady)
                        <section class="rounded-xl border border-[#E2E8F0] bg-white p-4 shadow-card">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#E8F2FF] text-[#0067FF]">
                                    <x-ui.icon name="check" class="h-4 w-4" />
                                </span>
                                <h2 class="text-sm font-semibold text-[#181A1E]">Clinician review</h2>
                            </div>
                            <form id="analysis-review" method="POST" action="{{ route('doctor.appointments.analysis.review', [$appointment, $analysis]) }}" class="mt-4 space-y-4">
                                @csrf
                                @method('PUT')
                                @foreach ($brief['findings'] as $finding)
                                    <label class="flex items-start gap-3">
                                        <input type="checkbox" name="reviewed[]" value="{{ $finding['index'] }}" class="mt-1 rounded border-[#E2E8F0] text-[#0067FF] focus:ring-[#0067FF]" @checked(in_array($finding['index'], old('reviewed', $reviewed), false))>
                                        <span class="min-w-0">
                                            <span class="block text-sm font-semibold text-[#181A1E]">{{ $finding['title'] }}</span>
                                            @if ($finding['line'] !== '')
                                                <span class="mt-0.5 block text-xs leading-5 text-[#64748B]">{{ $finding['line'] }}</span>
                                            @endif
                                        </span>
                                    </label>
                                @endforeach
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold text-[#181A1E]" for="doctor_notes">Add clinical note (optional)</label>
                                    <textarea id="doctor_notes" name="doctor_notes" rows="3" class="field" placeholder="Add a note...">{{ old('doctor_notes', $analysis->doctor_notes) }}</textarea>
                                </div>
                                <details @if ($errors->has('clinician_summary')) open @endif>
                                    <summary class="cursor-pointer text-xs font-semibold text-[#0067FF]">Edit summary</summary>
                                    <textarea id="clinician_summary" name="clinician_summary" rows="4" class="field mt-2">{{ old('clinician_summary', $analysis->clinician_summary ?: $analysis->original_summary) }}</textarea>
                                </details>
                            </form>
                            <form id="analysis-approve" method="POST" action="{{ route('doctor.appointments.analysis.approve', [$appointment, $analysis]) }}">
                                @csrf
                            </form>
                            <div class="mt-3 grid grid-cols-2 gap-2">
                                <button type="submit" form="analysis-review" class="btn-secondary !px-3">Save draft</button>
                                <button type="submit" form="analysis-approve" class="btn-primary !px-3">Approve</button>
                            </div>
                        </section>

                        <section class="rounded-xl border border-[#E2E8F0] bg-white p-4 shadow-card">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#E8F2FF] text-[#0067FF]">
                                    <x-ui.icon name="file" class="h-4 w-4" />
                                </span>
                                <h2 class="text-sm font-semibold text-[#181A1E]">Analysis details</h2>
                            </div>
                            <dl class="mt-4 space-y-3 text-sm">
                                <div class="flex items-start gap-3">
                                    <x-ui.icon name="clock" class="mt-0.5 h-4 w-4 text-[#64748B]" />
                                    <div>
                                        <dt class="text-xs text-[#64748B]">Generated</dt>
                                        <dd class="font-medium text-[#181A1E]">{{ $analysis->completed_at?->format('j M Y, g:i A') ?: 'Just now' }}</dd>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <x-ui.icon name="file" class="mt-0.5 h-4 w-4 text-[#64748B]" />
                                    <div>
                                        <dt class="text-xs text-[#64748B]">Source</dt>
                                        <dd class="font-medium text-[#181A1E]">{{ $brief['source_label'] }}</dd>
                                    </div>
                                </div>
                            </dl>
                            @if ($analyses->count() > 1)
                                <h3 class="mt-4 text-xs font-semibold uppercase tracking-wide text-[#64748B]">Analysis history</h3>
                                <ul class="mt-2 divide-y divide-[#E2E8F0]">
                                    @foreach ($analyses as $item)
                                        <li class="py-2">
                                            <a href="{{ route('doctor.appointments.analysis', [$appointment, $item]) }}" class="block text-sm no-underline {{ $analysis && $analysis->id === $item->id ? 'font-semibold text-[#0067FF]' : 'text-[#181A1E]' }}">
                                                {{ $item->created_at?->format('j M Y, g:i A') }}
                                                <span class="block text-xs font-medium text-[#64748B]">{{ $item->statusLabel() }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </section>
                    @elseif ($analyses->count() > 1)
                        <section class="rounded-xl border border-[#E2E8F0] bg-white px-4 py-3 shadow-card">
                            <h2 class="text-sm font-semibold text-[#181A1E]">Analysis history</h2>
                            <ul class="mt-2 divide-y divide-[#E2E8F0]">
                                @foreach ($analyses as $item)
                                    <li class="py-2">
                                        <a href="{{ route('doctor.appointments.analysis', [$appointment, $item]) }}" class="block text-sm no-underline {{ $analysis && $analysis->id === $item->id ? 'font-semibold text-[#0067FF]' : 'text-[#181A1E]' }}">
                                            {{ $item->created_at?->format('j M Y, g:i A') }}
                                            <span class="block text-xs font-medium text-[#64748B]">{{ $item->statusLabel() }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </aside>
            </div>
            @endif
        </div>
    </div>
@endsection
