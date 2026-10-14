@php
    $summary = is_array($summary ?? null) ? $summary : [];
    $reports = is_array($reports ?? null) ? $reports : [];
    $summaryText = $summary['text'] ?? null;
    $briefText = $summary['referral_brief'] ?? null;
    $summarySource = $summary['source'] ?? null;
    $highlights = array_values(array_filter($summary['highlights'] ?? [], fn ($item) => filled($item)));
    $findings = array_values(array_filter($summary['findings'] ?? [], fn ($item) => filled($item['title'] ?? null)));
    $steps = array_values(array_filter($summary['steps'] ?? [], fn ($step) => filled($step)));
    $notes = array_values(array_filter($summary['report_notes'] ?? [], fn ($note) => is_array($note)));
    $sourceLabel = match ($summarySource) {
        'approved' => 'Approved',
        'analysis' => 'Analysis',
        default => 'From the chart',
    };
    $sourceClass = $summarySource === 'approved' ? 'badge-success' : 'badge-info';
@endphp

@if (filled($summaryText) || filled($briefText) || $reports !== [] || $notes !== [])
    <section class="mt-6 overflow-hidden rounded-xl border border-line bg-white shadow-card">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line px-5 py-4">
            <div>
                <h2 class="text-base font-semibold text-[#0B1F3A]">Patient summary</h2>
                <p class="mt-1 text-sm leading-6 text-[#53647D]">
                    @if ($summarySource === 'approved')
                        Approved for this referral{{ $reports !== [] ? ' and sent with the reports below' : '' }}.
                    @elseif ($summarySource === 'analysis')
                        Prepared from this visit{{ $reports !== [] ? ' and sent with the reports below' : '' }}.
                    @else
                        Taken from the visit chart and sent with this referral.
                    @endif
                </p>
            </div>
            @if (filled($summaryText) || filled($briefText))
                <span class="{{ $sourceClass }}">{{ $sourceLabel }}</span>
            @endif
        </div>

        <div class="px-5 py-5">
            @if ($highlights !== [])
                <div class="flex flex-wrap gap-2">
                    @foreach ($highlights as $highlight)
                        <span class="rounded-full bg-[#E8F2FF] px-2.5 py-1 text-xs font-semibold text-[#0067FF]">{{ $highlight }}</span>
                    @endforeach
                </div>
            @endif

            @if (filled($summaryText))
                <p class="text-[15px] font-medium leading-7 text-[#0B1F3A] {{ $highlights !== [] ? 'mt-4' : '' }}">{{ $summaryText }}</p>
            @endif

            @if (filled($briefText) && $briefText !== $summaryText)
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-[#53647D]">{{ $briefText }}</p>
            @endif

            @if ($findings !== [])
                <div class="mt-5 border-t border-line pt-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[#53647D]">Key findings</h3>
                    <div class="mt-3 divide-y divide-[#E2EAF4]">
                        @foreach ($findings as $finding)
                            <div class="py-3 first:pt-0 last:pb-0">
                                <p class="text-sm font-semibold text-[#0B1F3A]">{{ $finding['title'] }}</p>
                                @if (filled($finding['value'] ?? null) || filled($finding['range'] ?? null))
                                    <p class="mt-0.5 text-sm text-[#0B1F3A]">
                                        {{ $finding['value'] ?? '' }}
                                        @if (filled($finding['range'] ?? null))
                                            <span class="text-[#53647D]">· reference {{ $finding['range'] }}</span>
                                        @endif
                                    </p>
                                @endif
                                @if (filled($finding['explanation'] ?? null))
                                    <p class="mt-1 text-sm leading-6 text-[#53647D]">{{ $finding['explanation'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($steps !== [])
                <div class="mt-5 border-t border-line pt-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[#53647D]">Suggested review</h3>
                    <ol class="mt-3 space-y-2">
                        @foreach ($steps as $step)
                            <li class="flex items-start gap-3 text-sm leading-6 text-[#0B1F3A]">
                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#E8F2FF] text-[11px] font-semibold text-[#0067FF]">{{ $loop->iteration }}</span>
                                <span>{{ $step }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif

            @if (filled($summary['limitations'] ?? null))
                <p class="mt-5 rounded-xl bg-[#F4F7FB] px-4 py-3 text-sm leading-6 text-[#53647D]">{{ $summary['limitations'] }}</p>
            @endif
        </div>
    </section>

    <section class="mt-6 overflow-hidden rounded-xl border border-line bg-white shadow-card">
        <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-4">
            <div>
                <h2 class="text-base font-semibold text-[#0B1F3A]">Reports</h2>
                <p class="mt-1 text-sm text-[#53647D]">Files included with this referral.</p>
            </div>
            <span class="badge-info">{{ count($reports) === 1 ? '1 file' : count($reports).' files' }}</span>
        </div>

        @if ($reports === [] && $notes === [])
            <p class="px-5 py-5 text-sm leading-6 text-[#53647D]">No reports were attached to this referral.</p>
        @else
            <div class="space-y-5 px-5 py-5">
                @forelse ($reports as $report)
                    @php
                        $note = collect($notes)->first(fn ($item) => (int) ($item['report_id'] ?? 0) === (int) ($report['id'] ?? 0));
                    @endphp
                    <article>
                        @if (filled($report['url'] ?? null))
                            <a href="{{ $report['url'] }}" target="_blank" rel="noopener" class="flex items-center gap-4 rounded-xl border border-[#E2EAF4] bg-[#F4F7FB] px-4 py-3 no-underline hover:border-[#1769FF]">
                        @else
                            <div class="flex items-center gap-4 rounded-xl border border-[#E2EAF4] bg-[#F4F7FB] px-4 py-3">
                        @endif
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-[#1769FF] shadow-sm">
                                <x-ui.icon name="file" class="h-5 w-5" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-[#0B1F3A]">{{ $report['title'] ?? 'Report' }}</span>
                                <span class="mt-0.5 block text-xs text-[#53647D]">
                                    {{ $report['kind'] ?? 'File' }}
                                    @if (filled($report['group'] ?? null) && ($report['group'] ?? '') !== ($report['title'] ?? ''))
                                        · {{ $report['group'] }}
                                    @endif
                                </span>
                            </span>
                            @if (filled($report['url'] ?? null))
                                <span class="shrink-0 text-sm font-semibold text-[#1769FF]">Open</span>
                            @endif
                        @if (filled($report['url'] ?? null))
                            </a>
                        @else
                            </div>
                        @endif

                        @if (is_array($note))
                            @include('dashboard.doctor.referrals.partials.report-reading', ['note' => $note])
                        @endif
                    </article>
                @empty
                    @foreach ($notes as $note)
                        <article>
                            <p class="text-sm font-semibold text-[#0B1F3A]">{{ $note['label'] ?: 'Report' }}</p>
                            @include('dashboard.doctor.referrals.partials.report-reading', ['note' => $note])
                        </article>
                    @endforeach
                @endforelse

                @foreach ($notes as $note)
                    @php
                        $linked = collect($reports)->contains(fn ($report) => (int) ($report['id'] ?? 0) === (int) ($note['report_id'] ?? 0));
                    @endphp
                    @if (! $linked && $reports !== [])
                        <article>
                            <p class="text-sm font-semibold text-[#0B1F3A]">{{ $note['label'] ?: 'Report' }}</p>
                            @include('dashboard.doctor.referrals.partials.report-reading', ['note' => $note])
                        </article>
                    @endif
                @endforeach
            </div>
        @endif
    </section>
@endif
