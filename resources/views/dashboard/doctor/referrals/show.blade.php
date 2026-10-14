@extends('layouts.dashboard')

@php
    $snapshot = $snapshot ?? [];
    $patient = $snapshot['patient'] ?? [];
    $from = $snapshot['referring_doctor'] ?? [];
    $visit = $snapshot['visit'] ?? [];
    $reports = $referral->reports ?? collect();
    $incoming = $incoming ?? false;
    $recipient = $referral->toDoctor;

    $statusClass = [
        'Sent' => 'badge-info',
        'Accepted' => 'badge-success',
        'Declined' => 'badge-danger',
        'Appointment booked' => 'badge-success',
    ];
    $urgencyClass = [
        'urgent' => 'badge-danger',
        'soon' => 'badge-warning',
        'routine' => 'badge-info',
    ];
    $fromLabel = trim(($from['name'] ?? 'Doctor').(! empty($from['specialization']) ? ' ('.$from['specialization'].')' : ''));
    $toLabel = trim(($recipient?->user?->name ?? 'Doctor').($recipient?->specialization ? ' ('.$recipient->specialization.')' : ''));
@endphp

@section('content')
    <x-ui.page-header
        title="{{ $patient['name'] ?? 'Patient' }}"
        description="{{ $incoming ? 'From '.$fromLabel : 'To '.$toLabel }} · {{ $sent }}"
    >
        <span class="{{ $urgencyClass[$referral->urgency] ?? 'badge-info' }}">{{ $referral->urgencyLabel() }}</span>
        <span class="{{ $statusClass[$status] ?? 'badge-info' }}">{{ $status }}</span>
        <a href="{{ route('doctor.referrals') }}" class="btn-secondary">Back</a>
    </x-ui.page-header>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-[#b7ebc9] bg-[#e8f8ef] px-5 py-4 text-sm font-medium text-[#0f9f6e]">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <p class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-600">{{ $errors->first() }}</p>
    @endif

    <section class="mt-6 rounded-xl border border-line bg-white p-5 shadow-card">
        <h2 class="text-lg font-bold text-[#0B1F3A]">Patient</h2>
        <div class="mt-6 grid gap-5 sm:grid-cols-3">
            @include('dashboard.partials.detail', ['label' => 'Name', 'value' => $patient['name'] ?? null])
            @include('dashboard.partials.detail', ['label' => 'Age', 'value' => $patient['age'] ?? null])
            @include('dashboard.partials.detail', ['label' => 'Gender', 'value' => filled($patient['gender'] ?? null) ? ucfirst((string) $patient['gender']) : null])
            @include('dashboard.partials.detail', ['label' => 'Blood type', 'value' => $patient['blood_type'] ?? null])
        </div>
        <div class="mt-6">
            @include('dashboard.partials.health-notes', ['healthNotes' => $patient['health_notes'] ?? []])
        </div>
    </section>

    <section class="mt-6 rounded-xl border border-line bg-white p-5 shadow-card">
        <h2 class="text-lg font-bold text-[#0B1F3A]">Visit snapshot</h2>
        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            @include('dashboard.partials.detail', ['label' => 'Date', 'value' => $visit['date'] ?? null])
            @include('dashboard.partials.detail', ['label' => 'Reason', 'value' => $visit['reason'] ?? null])
        </div>
        <p class="mt-6 whitespace-pre-line text-sm leading-6 text-[#53647D]">
            <span class="font-semibold text-[#0B1F3A]">Notes.</span>
            {{ $visit['notes'] ?? 'Not recorded' }}
        </p>
        <p class="mt-3 text-sm leading-6 text-[#53647D]">
            <span class="font-semibold text-[#0B1F3A]">Diagnosis.</span>
            {{ $visit['diagnosis'] ?? 'Not recorded' }}
        </p>
        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-[#53647D]">
            <span class="font-semibold text-[#0B1F3A]">Plan.</span>
            {{ $visit['plan'] ?? 'Not recorded' }}
        </p>
        <p class="mt-6 text-sm leading-6 text-[#53647D]">
            <span class="font-semibold text-[#0B1F3A]">Referral reason.</span>
            {{ $referral->reason }}
        </p>
        @if (filled($referral->clinical_question))
            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-[#53647D]">
                <span class="font-semibold text-[#0B1F3A]">Clinical question.</span>
                {{ $referral->clinical_question }}
            </p>
        @endif
        @if ($referral->status === 'declined' && filled($referral->decline_reason))
            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-[#53647D]">
                <span class="font-semibold text-[#0B1F3A]">Decline reason.</span>
                {{ $referral->decline_reason }}
            </p>
        @endif
    </section>

    @php
        $packet = is_array($snapshot['patient_summary'] ?? null) ? $snapshot['patient_summary'] : [];
        $summaryText = filled($packet['text'] ?? null)
            ? $packet['text']
            : ($snapshot['approved_summary'] ?? $snapshot['ai_summary'] ?? null);
        $briefText = $packet['referral_brief'] ?? $snapshot['referral_brief'] ?? null;
        $summarySource = $packet['source'] ?? (filled($snapshot['approved_summary'] ?? null) ? 'approved' : (filled($summaryText) ? 'analysis' : null));
        $summaryFindings = is_array($packet['findings'] ?? null) ? $packet['findings'] : [];
        $summarySteps = array_values(array_filter($packet['steps'] ?? [], fn ($step) => filled($step)));
        $summaryNotes = is_array($packet['report_notes'] ?? null) ? $packet['report_notes'] : [];
        $summaryHighlights = array_values(array_filter($packet['highlights'] ?? [], fn ($item) => filled($item)));
    @endphp

    @if (filled($summaryText) || filled($briefText) || $summaryFindings !== [] || $summaryNotes !== [])
        <section class="mt-6 rounded-xl border border-line bg-white p-5 shadow-card">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-[#0B1F3A]">Patient summary</h2>
                @if ($summarySource === 'approved')
                    <span class="badge-success">Approved</span>
                @elseif ($summarySource === 'analysis')
                    <span class="badge-info">Analysis</span>
                @else
                    <span class="badge-info">From the chart</span>
                @endif
            </div>
            <p class="mt-2 text-sm leading-6 text-[#53647D]">
                @if ($summarySource === 'approved')
                    The referring doctor approved this summary. It was saved with the reports at the time of referral.
                @elseif ($summarySource === 'analysis')
                    This summary was prepared from the visit analysis and saved with the selected reports. It has not been marked approved.
                @else
                    No analysis summary was available, so this brief was taken from the visit chart and the reports included below.
                @endif
            </p>
            @if ($summaryHighlights !== [])
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($summaryHighlights as $highlight)
                        <span class="rounded-full bg-[#E8F2FF] px-2.5 py-1 text-xs font-semibold text-[#0067FF]">{{ $highlight }}</span>
                    @endforeach
                </div>
            @endif
            @if (filled($summaryText))
                <p class="mt-4 whitespace-pre-line text-sm font-semibold leading-6 text-[#0B1F3A]">{{ $summaryText }}</p>
            @endif
            @if (filled($briefText) && $briefText !== $summaryText)
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-[#53647D]">{{ $briefText }}</p>
            @endif
            @if (filled($packet['limitations'] ?? null))
                <p class="mt-3 text-sm leading-6 text-[#53647D]">{{ $packet['limitations'] }}</p>
            @endif

            @if ($summaryFindings !== [])
                <div class="mt-5 divide-y divide-[#E2EAF4]">
                    @foreach ($summaryFindings as $finding)
                        <div class="py-3 first:pt-0 last:pb-0">
                            <p class="text-sm font-semibold text-[#0B1F3A]">{{ $finding['title'] ?? 'Finding' }}</p>
                            @if (filled($finding['value'] ?? null) || filled($finding['range'] ?? null))
                                <p class="mt-0.5 text-sm text-[#0B1F3A]">
                                    {{ $finding['value'] ?? '' }}
                                    @if (filled($finding['range'] ?? null))
                                        <span class="text-[#53647D]"> · range {{ $finding['range'] }}</span>
                                    @endif
                                </p>
                            @endif
                            @if (filled($finding['explanation'] ?? null))
                                <p class="mt-1 text-sm leading-6 text-[#53647D]">{{ $finding['explanation'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($summarySteps !== [])
                <ol class="mt-5 space-y-2">
                    @foreach ($summarySteps as $step)
                        <li class="flex items-start gap-3 text-sm leading-6 text-[#0B1F3A]">
                            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#E8F2FF] text-[11px] font-semibold text-[#0067FF]">{{ $loop->iteration }}</span>
                            <span>{{ $step }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif

            @if ($summaryNotes !== [])
                <div class="mt-5 space-y-4">
                    <h3 class="text-sm font-semibold text-[#0B1F3A]">Report readings</h3>
                    @foreach ($summaryNotes as $note)
                        @php
                            $linked = $reports->firstWhere('id', (int) ($note['report_id'] ?? 0));
                        @endphp
                        <div class="rounded-xl border border-[#E2EAF4] bg-[#F4F7FB] px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-[#0B1F3A]">{{ $note['label'] ?: ($linked?->original_name ?: 'Report') }}</p>
                                @if ($linked)
                                    <a href="{{ route('doctor.referrals.reports.show', [$referral, $linked]) }}" class="text-xs font-semibold text-[#1769FF] no-underline">Open file</a>
                                @endif
                            </div>
                            @if (filled($note['narrative'] ?? null))
                                <p class="mt-2 text-sm leading-6 text-[#53647D]">{{ $note['narrative'] }}</p>
                            @endif
                            @if (($note['rows'] ?? []) !== [])
                                <dl class="mt-2 space-y-1">
                                    @foreach ($note['rows'] as $row)
                                        <div class="flex flex-wrap justify-between gap-x-3 text-sm">
                                            <dt class="font-medium text-[#0B1F3A]">{{ $row['parameter'] ?: 'Result' }}</dt>
                                            <dd class="text-[#53647D]">
                                                {{ $row['value'] ?: '—' }}
                                                @if (filled($row['range'] ?? null))
                                                    · {{ $row['range'] }}
                                                @endif
                                                @if (filled($row['flag'] ?? null) && ! in_array(strtolower((string) $row['flag']), ['normal', 'unknown'], true))
                                                    <span class="font-semibold text-[#C2410C]">{{ ucfirst((string) $row['flag']) }}</span>
                                                @endif
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    <section class="mt-6 rounded-xl border border-line bg-white p-5 shadow-card">
        <h2 class="text-lg font-bold text-[#0B1F3A]">Attached reports</h2>
        @if ($reports->isEmpty())
            <p class="mt-3 text-sm leading-6 text-[#53647D]">No reports were attached to this visit.</p>
        @else
            <div class="mt-4 space-y-3">
                @foreach ($reports as $report)
                    <a href="{{ route('doctor.referrals.reports.show', [$referral, $report]) }}" class="flex items-center justify-between gap-3 rounded-xl border border-[#E2EAF4] bg-[#F4F7FB] px-4 py-3 hover:border-[#1769FF]">
                        <span>
                            <span class="block text-sm font-semibold text-[#0B1F3A]">{{ $report->original_name }}</span>
                            <span class="mt-0.5 block text-xs font-semibold text-[#53647D]">{{ $report->group_name }}</span>
                        </span>
                        <x-ui.icon name="download" class="h-4 w-4 shrink-0 text-[#1769FF]" />
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    @if ($incoming && $referral->status === 'accepted')
        <section class="mt-6 rounded-2xl border border-[#E2EAF4] bg-white p-6">
            <h2 class="text-lg font-bold text-[#0B1F3A]">Book the referred visit</h2>
            <p class="mt-2 text-sm leading-6 text-[#53647D]">Choose an open time on your schedule. The appointment is confirmed only when that slot is still available.</p>
            @if (($openSlots ?? []) === [])
                <p class="mt-4 text-sm leading-6 text-[#53647D]">No open times are available in the booking window. Add hours on your schedule, then return here.</p>
            @else
                <form method="POST" action="{{ route('doctor.referrals.schedule', $referral) }}" class="mt-4 max-w-xl space-y-4">
                    @csrf
                    @include('partials.slot-picker', ['openSlots' => $openSlots])
                    <button type="submit" class="btn-primary">Book appointment</button>
                </form>
            @endif
        </section>
    @endif

    @if ($incoming && $referral->status === 'appointment_booked' && $referral->proposedAppointment)
        <div class="mt-6">
            <a href="{{ route('doctor.appointments.show', $referral->proposedAppointment) }}" class="btn-primary">Open the booked visit</a>
        </div>
    @endif

    @if (! $incoming)
        <p class="mt-6 text-sm leading-6 text-[#53647D]">
            @if ($referral->status === 'sent')
                Waiting for {{ $toLabel }} to accept or decline.
            @elseif ($referral->status === 'accepted')
                {{ $toLabel }} accepted. They can book the visit from their schedule.
            @elseif ($referral->status === 'appointment_booked')
                {{ $toLabel }} booked the referred visit.
            @elseif ($referral->status === 'declined')
                {{ $toLabel }} declined this referral.
            @endif
        </p>
    @endif

    @if ($incoming && $referral->status === 'sent')
        <div class="mt-6 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('doctor.referrals.accept', $referral) }}">
                @csrf
                <button type="submit" class="inline-flex h-12 items-center justify-center rounded-xl bg-[#1769FF] px-6 font-semibold text-white">Accept</button>
            </form>
        </div>
        <form method="POST" action="{{ route('doctor.referrals.decline', $referral) }}" class="mt-4 max-w-xl space-y-3">
            @csrf
            <label class="block text-sm font-semibold text-[#0B1F3A]">
                Reason for declining
                <textarea name="decline_reason" rows="3" maxlength="2000" class="field mt-2" placeholder="Optional. The referring doctor sees this.">{{ old('decline_reason') }}</textarea>
            </label>
            <button type="submit" class="inline-flex h-12 items-center justify-center rounded-xl bg-red-50 px-6 font-semibold text-red-600">Decline</button>
        </form>
    @endif
@endsection
