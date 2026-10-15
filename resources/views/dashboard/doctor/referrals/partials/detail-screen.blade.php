@php
    $detail = is_array($detail ?? null) ? $detail : [];
    $patientName = $detail['patientName'] ?? 'Patient';
    $nameParts = preg_split('/\s+/u', trim((string) $patientName)) ?: [];
    $initials = '';
    foreach (array_slice(array_values(array_filter($nameParts)), 0, 2) as $part) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    $initials = $initials !== '' ? $initials : 'P';
    $meta = trim((string) ($detail['meta'] ?? ''));
    $known = trim((string) ($detail['known'] ?? ''));
    $status = (string) ($detail['status'] ?? 'Sent');
    $statusValue = (string) ($detail['statusValue'] ?? 'sent');
    $statusTone = match ($statusValue) {
        'accepted', 'appointment_booked' => 'border-[#15803D] text-[#15803D]',
        'declined' => 'border-[#B91C1C] text-[#B91C1C]',
        default => 'border-[#0067FF] text-[#0067FF]',
    };
    $statusIcon = match ($statusValue) {
        'accepted', 'appointment_booked' => 'check',
        'declined' => 'close',
        default => 'send',
    };
    $source = (string) ($detail['source'] ?? '');
    $highlights = array_values(array_filter($detail['highlights'] ?? [], fn ($item) => filled($item)));
    $findings = array_values(array_filter($detail['findings'] ?? [], fn ($item) => is_array($item) && filled($item['title'] ?? $item['explanation'] ?? null)));
    $steps = array_values(array_filter($detail['steps'] ?? [], fn ($step) => filled($step)));
    $summaryText = trim((string) ($detail['summaryText'] ?? ''));
    $briefText = trim((string) ($detail['briefText'] ?? ''));
    $limitations = trim((string) ($detail['limitations'] ?? ''));
    $reports = array_values(array_filter($detail['reports'] ?? [], fn ($report) => is_array($report)));
    $extraNotes = array_values(array_filter($detail['extraNotes'] ?? [], fn ($note) => is_array($note)));
    $visit = is_array($detail['visit'] ?? null) ? $detail['visit'] : [];
    $reportCount = count($reports);
    $attachmentLabel = $reportCount === 1 ? '1 attachment' : $reportCount.' attachments';
    $priorHaystack = strtolower(implode(' ', array_filter([
        $summaryText,
        $briefText,
        $limitations,
        implode(' ', $highlights),
        ...array_map(fn ($report) => (string) data_get($report, 'note.narrative', ''), $reports),
        ...array_map(fn ($note) => (string) ($note['narrative'] ?? ''), $extraNotes),
    ])));
    $priorResults = str_contains($priorHaystack, 'prior')
        || str_contains($priorHaystack, 'earlier')
        || str_contains($priorHaystack, 'previous');
    $labBlocks = [];
    foreach ($reports as $report) {
        $note = is_array($report['note'] ?? null) ? $report['note'] : null;
        if ($note && (filled($note['narrative'] ?? null) || ($note['rows'] ?? []) !== [])) {
            $labBlocks[] = $note;
        }
    }
    foreach ($extraNotes as $note) {
        if (filled($note['narrative'] ?? null) || ($note['rows'] ?? []) !== []) {
            $labBlocks[] = $note;
        }
    }
    $footerTone = (string) ($detail['footerTone'] ?? 'waiting');
    $footerIcon = match ($footerTone) {
        'done' => 'check',
        'alert' => 'alert',
        default => 'clock',
    };
@endphp

@if (session('status'))
    <p class="mb-4 rounded-xl border border-[#BBF7D0] bg-[#F0FDF4] px-4 py-3 text-sm font-medium text-success">{{ session('status') }}</p>
@endif

@if ($errors->any())
    <p class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-danger">{{ $errors->first() }}</p>
@endif

<div class="mb-4 flex flex-wrap items-start justify-between gap-3">
    <div class="min-w-0">
        <p class="text-sm text-muted">
            <a href="{{ route('doctor.referrals') }}" class="text-muted no-underline hover:text-primary">Referrals</a>
            <span class="px-1.5 text-[#CBD5E1]">/</span>
            <span class="text-heading">Referral details</span>
        </p>
        <h1 class="mt-1 text-[28px] font-semibold leading-tight tracking-tight text-heading">Referral details</h1>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        @if ($statusValue !== 'sent')
            <span class="inline-flex h-10 items-center gap-2 rounded-lg border bg-white px-3 text-sm font-semibold {{ $statusTone }}">
                <x-ui.icon name="{{ $statusIcon }}" class="h-4 w-4" />
                {{ $status }}
            </span>
        @endif
        <a href="{{ route('doctor.referrals') }}" class="btn-secondary">
            <x-ui.icon name="chevron-left" class="h-4 w-4" />
            Back to referrals
        </a>
    </div>
</div>

<section class="rounded-xl border border-line bg-white p-4 shadow-card sm:p-5">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex min-w-0 items-center gap-3">
            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#E8F2FF] text-sm font-semibold text-primary">{{ $initials }}</span>
            <div class="min-w-0">
                <p class="truncate text-base font-semibold text-heading">{{ $patientName }}</p>
                @if ($meta !== '' || $known !== '')
                    <p class="mt-0.5 text-sm text-body">
                        @if ($meta !== '')
                            <span>{{ $meta }}</span>
                        @endif
                        @if ($known !== '')
                            @if ($meta !== '')
                                <span class="px-1.5 text-[#CBD5E1]">|</span>
                            @endif
                            <span>Known condition: {{ $known }}</span>
                        @endif
                    </p>
                @endif
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3 lg:min-w-[34rem] lg:gap-8">
            <div class="flex items-start gap-2.5">
                <x-ui.icon name="building" class="mt-0.5 h-4 w-4 shrink-0 text-muted" />
                <div class="min-w-0">
                    <p class="text-xs text-muted">Referral clinic</p>
                    <p class="truncate text-sm font-semibold text-heading">{{ filled($detail['clinic'] ?? null) ? $detail['clinic'] : 'Not recorded' }}</p>
                </div>
            </div>
            <div class="flex items-start gap-2.5">
                <x-ui.icon name="user" class="mt-0.5 h-4 w-4 shrink-0 text-muted" />
                <div class="min-w-0">
                    <p class="text-xs text-muted">Referred to</p>
                    <p class="truncate text-sm font-semibold text-heading">{{ $detail['referredTo'] ?? 'Doctor' }}</p>
                    @if (filled($detail['referredToSpecialty'] ?? null))
                        <p class="truncate text-xs text-muted">{{ $detail['referredToSpecialty'] }}</p>
                    @endif
                </div>
            </div>
            <div class="flex items-start gap-2.5">
                <x-ui.icon name="calendar" class="mt-0.5 h-4 w-4 shrink-0 text-muted" />
                <div class="min-w-0">
                    <p class="text-xs text-muted">Date sent</p>
                    <p class="text-sm font-semibold text-heading">{{ $detail['sent'] ?? 'Not recorded' }}</p>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="mt-4 grid items-start gap-4 lg:grid-cols-2">
    <div class="space-y-4">
        <section class="rounded-xl border border-line bg-white p-5 shadow-card">
            <div class="flex items-center gap-2">
                <x-ui.icon name="file" class="h-4 w-4 text-primary" />
                <h2 class="text-base font-semibold text-heading">Referral overview</h2>
            </div>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm text-muted">Status</dt>
                    <dd class="mt-1 text-sm font-semibold text-heading">{{ $status }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Referred to</dt>
                    <dd class="mt-1 text-sm font-semibold text-heading">
                        {{ $detail['referredTo'] ?? 'Doctor' }}
                        @if (filled($detail['referredToSpecialty'] ?? null))
                            ({{ $detail['referredToSpecialty'] }})
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Referred by</dt>
                    <dd class="mt-1 text-sm font-semibold text-heading">{{ $detail['referredBy'] ?? 'Doctor' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted">Date sent</dt>
                    <dd class="mt-1 text-sm font-semibold text-heading">{{ $detail['sent'] ?? 'Not recorded' }}</dd>
                </div>
            </dl>
            <div class="mt-4 border-t border-line pt-4">
                <p class="text-sm text-muted">Reason for referral</p>
                <p class="mt-1 text-sm font-medium leading-6 text-heading">{{ filled($detail['reason'] ?? null) ? $detail['reason'] : 'Not recorded' }}</p>
            </div>
            @if (filled($detail['question'] ?? null))
                <div class="mt-4">
                    <p class="text-sm text-muted">Clinical question</p>
                    <p class="mt-1 whitespace-pre-line text-sm leading-6 text-heading">{{ $detail['question'] }}</p>
                </div>
            @endif
            @if (filled($detail['declineReason'] ?? null))
                <div class="mt-4">
                    <p class="text-sm text-muted">Decline reason</p>
                    <p class="mt-1 whitespace-pre-line text-sm leading-6 text-heading">{{ $detail['declineReason'] }}</p>
                </div>
            @endif
            @if (filled($visit['notes'] ?? null) || filled($visit['diagnosis'] ?? null) || filled($visit['plan'] ?? null))
                <div class="mt-4 space-y-3 border-t border-line pt-4">
                    @if (filled($visit['notes'] ?? null))
                        <p class="text-sm leading-6 text-body"><span class="font-semibold text-heading">Notes.</span> {{ $visit['notes'] }}</p>
                    @endif
                    @if (filled($visit['diagnosis'] ?? null))
                        <p class="text-sm leading-6 text-body"><span class="font-semibold text-heading">Diagnosis.</span> {{ $visit['diagnosis'] }}</p>
                    @endif
                    @if (filled($visit['plan'] ?? null))
                        <p class="text-sm leading-6 text-body"><span class="font-semibold text-heading">Plan.</span> {{ $visit['plan'] }}</p>
                    @endif
                </div>
            @endif
        </section>

        <section class="rounded-xl border border-line bg-white p-5 shadow-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex items-start gap-2">
                    <x-ui.icon name="spark" class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                    <div>
                        <h2 class="text-base font-semibold text-heading">AI-assisted patient summary</h2>
                        <p class="mt-0.5 text-xs text-muted">For clinician review · Not a diagnosis</p>
                    </div>
                </div>
                @if ($source === 'approved')
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-[#F0FDF4] px-2.5 py-1 text-xs font-semibold text-success">
                        <x-ui.icon name="check" class="h-3.5 w-3.5" />
                        Reviewed and approved
                    </span>
                @elseif ($source === 'analysis')
                    <span class="badge-info">Prepared for review</span>
                @elseif ($summaryText !== '' || $briefText !== '')
                    <span class="badge-info">From the chart</span>
                @endif
            </div>

            @if ($highlights !== [])
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($highlights as $highlight)
                        <span class="rounded-full bg-[#E8F2FF] px-2.5 py-1 text-xs font-semibold text-primary">{{ $highlight }}</span>
                    @endforeach
                </div>
            @endif

            @if ($summaryText !== '')
                <p class="mt-4 text-sm leading-6 text-heading">{{ $summaryText }}</p>
            @elseif ($briefText === '' && $findings === [] && $steps === [])
                <p class="mt-4 text-sm leading-6 text-body">No summary was saved with this referral.</p>
            @endif

            @if ($briefText !== '' && $briefText !== $summaryText)
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-body">{{ $briefText }}</p>
            @endif

            @if ($findings !== [])
                <div class="mt-4">
                    <div class="flex items-center gap-2">
                        <x-ui.icon name="info" class="h-4 w-4 text-primary" />
                        <h3 class="text-sm font-semibold text-heading">Key findings</h3>
                    </div>
                    <div class="mt-2 space-y-2">
                        @foreach ($findings as $finding)
                            <p class="text-sm leading-6 text-body">
                                @if (filled($finding['explanation'] ?? null))
                                    {{ $finding['explanation'] }}
                                @else
                                    {{ $finding['title'] ?? 'Finding' }}
                                    @if (filled($finding['value'] ?? null))
                                        {{ $finding['value'] }}
                                    @endif
                                @endif
                            </p>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($steps !== [])
                <div class="mt-4">
                    <div class="flex items-center gap-2">
                        <x-ui.icon name="clipboard" class="h-4 w-4 text-primary" />
                        <h3 class="text-sm font-semibold text-heading">Suggested review</h3>
                    </div>
                    <ol class="mt-2 space-y-2">
                        @foreach ($steps as $step)
                            <li class="flex items-start gap-2.5 text-sm leading-6 text-heading">
                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#E8F2FF] text-[11px] font-semibold text-primary">{{ $loop->iteration }}</span>
                                <span>{{ $step }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif

            @if ($limitations !== '')
                <p class="mt-4 flex items-start gap-2 rounded-lg border border-[#FDE68A] bg-[#FFFBEB] px-3 py-2.5 text-sm leading-6 text-[#92400E]">
                    <x-ui.icon name="alert" class="mt-0.5 h-4 w-4 shrink-0" />
                    <span>{{ $limitations }}</span>
                </p>
            @endif
        </section>
    </div>

    <section class="rounded-xl border border-line bg-white p-5 shadow-card">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <x-ui.icon name="paperclip" class="h-4 w-4 text-primary" />
                <h2 class="text-base font-semibold text-heading">Reports & attachments</h2>
            </div>
            @if ($reportCount > 0)
                <p class="text-sm text-muted">{{ $attachmentLabel }}</p>
            @endif
        </div>

        @if ($reports === [] && $labBlocks === [])
            <p class="mt-4 text-sm leading-6 text-body">No reports were attached to this referral.</p>
        @else
            @if ($reports !== [])
                <div class="mt-4 space-y-3">
                    @foreach ($reports as $report)
                        <div class="flex items-center gap-3 rounded-xl border border-line bg-[#F8FAFC] px-3 py-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#E8F2FF] text-primary">
                                <x-ui.icon name="file" class="h-4 w-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-heading">{{ $report['title'] ?? 'Report' }}</p>
                                <p class="mt-0.5 truncate text-xs text-muted">
                                    {{ $report['kind'] ?? 'File' }}
                                    @if (filled($report['filename'] ?? null) && ($report['filename'] ?? '') !== ($report['title'] ?? ''))
                                        · {{ $report['filename'] }}
                                    @endif
                                    @if (filled($report['date'] ?? null))
                                        · {{ $report['date'] }}
                                    @endif
                                </p>
                            </div>
                            @if (filled($report['url'] ?? null))
                                <a href="{{ $report['url'] }}" target="_blank" rel="noopener" class="inline-flex h-9 shrink-0 items-center rounded-lg border border-primary bg-white px-3.5 text-sm font-semibold text-primary no-underline hover:bg-[#E8F2FF]">Open</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @foreach ($labBlocks as $note)
                @php
                    $rows = array_values(array_filter($note['rows'] ?? [], fn ($row) => is_array($row)));
                @endphp
                <div class="mt-5">
                    <h3 class="text-sm font-semibold text-heading">Lab results from attached report</h3>
                    @if (filled($note['narrative'] ?? null))
                        <p class="mt-1 text-sm leading-6 text-body">{{ $note['narrative'] }}</p>
                    @endif
                    @if ($rows !== [])
                        <div class="mt-3 overflow-x-auto">
                            <table class="w-full min-w-[520px] text-left text-sm">
                                <thead>
                                    <tr class="border-b border-line text-xs font-medium text-muted">
                                        <th class="py-2 pr-3 font-medium">Parameter</th>
                                        <th class="py-2 pr-3 font-medium">Result</th>
                                        <th class="py-2 pr-3 font-medium">Reference range</th>
                                        <th class="py-2 font-medium">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rows as $row)
                                        @php
                                            $flag = strtolower(trim((string) ($row['flag'] ?? '')));
                                            $abnormal = $flag !== '' && ! in_array($flag, ['normal', 'unknown'], true);
                                        @endphp
                                        <tr class="border-b border-line last:border-0">
                                            <td class="py-2.5 pr-3 font-medium text-heading">{{ $row['parameter'] ?: 'Result' }}</td>
                                            <td class="py-2.5 pr-3 tabular-nums text-heading">{{ filled($row['value'] ?? null) ? $row['value'] : '—' }}</td>
                                            <td class="py-2.5 pr-3 tabular-nums text-body">{{ filled($row['range'] ?? null) ? $row['range'] : '—' }}</td>
                                            <td class="py-2.5">
                                                @if ($flag === '' || $flag === 'unknown')
                                                    <span class="text-muted">—</span>
                                                @elseif ($abnormal)
                                                    <span class="font-semibold text-[#C2410C]">{{ ucfirst($flag) }}</span>
                                                @else
                                                    <span class="font-semibold text-success">Normal</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($priorResults)
                            <p class="mt-3 flex items-start gap-2 rounded-lg bg-[#E8F2FF] px-3 py-2.5 text-sm leading-6 text-[#1D4ED8]">
                                <x-ui.icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                                <span>These results are from a prior visit.</span>
                            </p>
                        @endif
                    @endif
                </div>
            @endforeach
        @endif
    </section>
</div>

@if (filled($detail['acceptUrl'] ?? null))
    <section class="mt-4 rounded-xl border border-line bg-white p-5 shadow-card">
        <h2 class="text-base font-semibold text-heading">Respond to this referral</h2>
        <div class="mt-4 flex flex-wrap gap-3">
            <form method="POST" action="{{ $detail['acceptUrl'] }}">
                @csrf
                <button type="submit" class="btn-primary">Accept</button>
            </form>
        </div>
        <form method="POST" action="{{ $detail['declineUrl'] }}" class="mt-4 max-w-xl space-y-3">
            @csrf
            <label class="block text-sm font-semibold text-heading">
                Reason for declining
                <textarea name="decline_reason" rows="3" maxlength="2000" class="field mt-2" placeholder="Optional. The referring doctor sees this.">{{ old('decline_reason') }}</textarea>
            </label>
            <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-red-50 px-4 text-sm font-semibold text-danger">Decline</button>
        </form>
    </section>
@endif

@if (! empty($detail['showBooking']))
    <section class="mt-4 rounded-xl border border-line bg-white p-5 shadow-card">
        <h2 class="text-base font-semibold text-heading">Book the referred visit</h2>
        <p class="mt-1 text-sm leading-6 text-body">Choose an open time on your schedule. The appointment is confirmed only when that slot is still available.</p>
        @if (($detail['openSlots'] ?? []) === [])
            <p class="mt-4 text-sm leading-6 text-body">No open times are available in the booking window. Add hours on your schedule, then return here.</p>
        @else
            <form method="POST" action="{{ $detail['scheduleUrl'] }}" class="mt-4 max-w-xl space-y-4">
                @csrf
                @include('partials.slot-picker', ['openSlots' => $detail['openSlots']])
                <button type="submit" class="btn-primary">Book appointment</button>
            </form>
        @endif
    </section>
@endif

<div class="mt-4 flex items-center justify-between gap-3 rounded-xl border border-line bg-white px-4 py-3 shadow-card" x-data="{ menu: false }">
    <p class="flex min-w-0 items-center gap-2 text-sm text-body">
        <x-ui.icon name="{{ $footerIcon }}" class="h-4 w-4 shrink-0 text-muted" />
        <span class="min-w-0">
            {{ $detail['footer'] ?? '' }}
            @if (filled($detail['bookedUrl'] ?? null))
                <a href="{{ $detail['bookedUrl'] }}" class="ml-2 font-semibold text-primary no-underline hover:underline">Open the booked visit</a>
            @endif
        </span>
    </p>
    <div class="relative shrink-0">
        <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-muted hover:bg-page hover:text-heading" @click="menu = ! menu" aria-label="More actions" :aria-expanded="menu ? 'true' : 'false'">
            <x-ui.icon name="more" class="h-4 w-4" />
        </button>
        <div x-show="menu" x-cloak @click.outside="menu = false" class="absolute bottom-10 right-0 z-20 w-48 overflow-hidden rounded-xl border border-line bg-white py-1 shadow-lift">
            @if (filled($detail['patientUrl'] ?? null))
                <a href="{{ $detail['patientUrl'] }}" class="block px-3 py-2 text-sm font-medium text-heading no-underline hover:bg-page">Open patient chart</a>
            @endif
            <a href="{{ route('doctor.referrals') }}" class="block px-3 py-2 text-sm font-medium text-heading no-underline hover:bg-page">Back to referrals</a>
        </div>
    </div>
</div>
