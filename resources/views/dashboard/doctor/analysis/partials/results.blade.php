@php
    $brief = $brief ?? \App\Support\AnalysisBrief::present(is_array($analysis?->structured_output) ? $analysis->structured_output : [], $context);
    $output = is_array($analysis?->structured_output) ? $analysis->structured_output : [];
    $reports = $output['report_interpretations'] ?? [];
    $differentials = $output['differential_considerations'] ?? [];
    $missing = array_values(array_filter($output['missing_information'] ?? [], fn ($item) => filled($item)));
    $tone = [
        'orange' => ['chip' => 'bg-[#FFF7ED] text-[#C2410C]', 'icon' => 'bg-[#FFF7ED] text-[#EA580C]', 'badge' => 'bg-[#FFF7ED] text-[#C2410C]'],
        'blue' => ['chip' => 'bg-[#E8F2FF] text-[#0067FF]', 'icon' => 'bg-[#E8F2FF] text-[#0067FF]', 'badge' => 'bg-[#E8F2FF] text-[#0067FF]'],
        'green' => ['chip' => 'bg-[#ECFDF3] text-[#15803D]', 'icon' => 'bg-[#ECFDF3] text-[#15803D]', 'badge' => 'bg-[#ECFDF3] text-[#15803D]'],
        'gray' => ['chip' => 'bg-[#F1F5F9] text-[#475569]', 'icon' => 'bg-[#F1F5F9] text-[#475569]', 'badge' => 'bg-[#F1F5F9] text-[#475569]'],
    ];
    $reportUrl = function ($id) use ($appointment) {
        if (! $id) {
            return null;
        }

        $report = $appointment->reports->firstWhere('id', (int) $id);

        return $report ? route('doctor.appointments.reports.show', [$appointment, $report]) : null;
    };
    $present = function ($value): bool {
        $text = strtolower(trim((string) ($value ?? '')));

        return $text !== '' && ! in_array($text, ['not available', 'not stated', 'not provided', 'n/a', 'unknown', 'none'], true);
    };
@endphp

<div class="space-y-4">
    <section class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-card">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#E8F2FF] text-[#0067FF]">
                <x-ui.icon name="clipboard" class="h-5 w-5" />
            </span>
            <h2 class="text-base font-semibold text-[#181A1E]">Clinical overview</h2>
        </div>
        <p class="mt-4 text-sm leading-6 text-[#4E545F]">{{ $brief['overview'] }}</p>
        @if (count($brief['chips']) > 0)
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($brief['chips'] as $chip)
                    @php $style = $tone[$chip['tone']] ?? $tone['gray']; @endphp
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $style['chip'] }}">
                        <x-ui.icon :name="$chip['icon']" class="h-3.5 w-3.5" />
                        {{ $chip['label'] }}
                    </span>
                @endforeach
            </div>
        @endif
    </section>

    <section class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-card">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#E8F2FF] text-[#0067FF]">
                <x-ui.icon name="chart" class="h-5 w-5" />
            </span>
            <h2 class="text-base font-semibold text-[#181A1E]">Key findings</h2>
        </div>
        @if (count($brief['findings']) === 0)
            <p class="mt-4 text-sm text-[#4E545F]">No findings were returned.</p>
        @else
            <div class="mt-4 divide-y divide-[#E2E8F0]">
                @foreach ($brief['findings'] as $finding)
                    @php $style = $tone[$finding['tone']] ?? $tone['gray']; @endphp
                    <article class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $style['icon'] }}">
                                <x-ui.icon :name="$finding['icon']" class="h-4 w-4" />
                            </span>
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-[#181A1E]">{{ $finding['title'] }}</h3>
                                @if ($finding['line'] !== '')
                                    <p class="mt-0.5 text-sm leading-5 text-[#64748B]">{{ $finding['line'] }}</p>
                                @endif
                            </div>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $style['badge'] }}">{{ $finding['badge'] }}</span>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    @if (count($brief['steps']) > 0)
        <section class="rounded-xl border border-[#E2E8F0] bg-white p-5 shadow-card">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-[#E8F2FF] text-[#0067FF]">
                    <x-ui.icon name="check" class="h-5 w-5" />
                </span>
                <h2 class="text-base font-semibold text-[#181A1E]">Next steps</h2>
            </div>
            <ol class="mt-4 space-y-2.5">
                @foreach ($brief['steps'] as $step)
                    <li class="flex items-start gap-3 text-sm leading-5 text-[#181A1E]">
                        <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#E8F2FF] text-[11px] font-semibold text-[#0067FF]">{{ $loop->iteration }}</span>
                        <span>{{ $step }}</span>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    <details class="group rounded-xl border border-[#E2E8F0] bg-white shadow-card">
        <summary class="flex cursor-pointer list-none items-center gap-3 px-4 py-3.5 [&::-webkit-details-marker]:hidden">
            <x-ui.icon name="chevron" class="h-4 w-4 text-[#64748B] transition group-open:rotate-180" />
            <x-ui.icon name="file" class="h-4 w-4 text-[#64748B]" />
            <span class="flex-1 text-sm font-semibold text-[#181A1E]">View all source details</span>
            <x-ui.icon name="chevron-right" class="h-4 w-4 text-[#94A3B8] group-open:hidden" />
        </summary>
        <div class="space-y-5 border-t border-[#E2E8F0] px-5 py-4 text-sm">
            @if (filled($brief['full_summary']))
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[#64748B]">Overview</h3>
                    <p class="mt-2 leading-6 text-[#181A1E]">{{ $brief['full_summary'] }}</p>
                    @if (filled($brief['limitations']))
                        <p class="mt-2 leading-6 text-[#64748B]">{{ $brief['limitations'] }}</p>
                    @endif
                </div>
            @endif

            @if (count($brief['findings']) > 0)
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[#64748B]">Findings</h3>
                    <div class="mt-2 divide-y divide-[#E2E8F0]">
                        @foreach ($brief['findings'] as $finding)
                            <div class="py-2.5">
                                <p class="font-semibold text-[#181A1E]">{{ $finding['title'] }}</p>
                                <p class="mt-0.5 text-[#4E545F]">
                                    @if ($finding['value'] !== '')
                                        {{ $finding['value'] }}
                                        @if ($finding['range'] !== '')
                                            <span class="text-[#64748B]"> · range {{ $finding['range'] }}</span>
                                        @endif
                                    @endif
                                </p>
                                @if ($finding['full'] !== '' && $finding['full'] !== $finding['line'])
                                    <p class="mt-1 leading-6 text-[#4E545F]">{{ $finding['full'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (count($reports) > 0)
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[#64748B]">Reports</h3>
                    <div class="mt-2 space-y-4">
                        @foreach ($reports as $report)
                            @php $url = $reportUrl($report['source_report_id'] ?? null); @endphp
                            <div>
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-semibold text-[#181A1E]">{{ $report['source_label'] ?: ($report['document_type'] ?: 'Report') }}</p>
                                    @if ($url)
                                        <a href="{{ $url }}" target="_blank" rel="noopener" class="text-xs font-semibold text-[#0067FF] no-underline">Open</a>
                                    @endif
                                </div>
                                @if (filled($report['narrative'] ?? null))
                                    <p class="mt-1 leading-6 text-[#181A1E]">{{ $report['narrative'] }}</p>
                                @endif
                                @if (count($report['rows'] ?? []) > 0)
                                    <table class="mt-2 w-full text-left">
                                        <tbody class="divide-y divide-[#E2E8F0]">
                                            @foreach ($report['rows'] as $row)
                                                @php
                                                    $observed = trim(($row['observed_value'] ?? '').' '.($row['unit'] ?? ''));
                                                    $flag = strtolower(trim((string) ($row['flag'] ?? '')));
                                                @endphp
                                                <tr>
                                                    <td class="py-2 pr-3 font-medium text-[#181A1E]">{{ $present($row['parameter'] ?? null) ? $row['parameter'] : 'Result' }}</td>
                                                    <td class="py-2 pr-3 text-[#181A1E]">{{ $present($observed) ? $observed : '—' }}</td>
                                                    <td class="py-2 text-[#64748B]">
                                                        {{ $present($row['reference_range'] ?? null) ? $row['reference_range'] : '' }}
                                                        @if ($present($flag) && $flag !== 'normal')
                                                            <span class="ml-2 font-semibold text-[#C2410C]">{{ ucfirst($flag) }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (count($differentials) > 0)
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[#64748B]">Considerations</h3>
                    <div class="mt-2 space-y-2">
                        @foreach ($differentials as $item)
                            <p class="text-[#181A1E]"><span class="font-semibold">{{ $item['possibility'] }}</span>@if (filled($item['questions'] ?? null)) <span class="text-[#4E545F]"> — {{ $item['questions'] }}</span>@endif</p>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($missing !== [])
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[#64748B]">Not recorded</h3>
                    <ul class="mt-2 space-y-1 text-[#4E545F]">
                        @foreach ($missing as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </details>
</div>
