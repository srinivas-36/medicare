@if ($legacy)
    <section class="rounded-xl border border-[#E2E8F0] bg-white px-4 py-4">
        <h2 class="text-sm font-semibold text-[#181A1E]">Earlier visit brief</h2>
        <p class="mt-1 text-xs text-[#64748B]">Saved from an earlier analysis. It is not part of the visit note unless a doctor approved it.</p>
        @if (filled($legacy['summary']))
            <p class="mt-3 text-sm leading-6 text-[#181A1E]">{{ $legacy['summary'] }}</p>
        @endif
        @if (filled($legacy['picture']))
            <h3 class="mt-4 text-sm font-semibold text-[#181A1E]">Clinical picture</h3>
            <p class="mt-1 text-sm leading-6 text-[#4E545F]">{{ $legacy['picture'] }}</p>
        @endif
        @if (filled($legacy['history']))
            <h3 class="mt-4 text-sm font-semibold text-[#181A1E]">History</h3>
            <p class="mt-1 text-sm leading-6 text-[#4E545F]">{{ $legacy['history'] }}</p>
        @endif
        @if (count($legacy['points']) > 0)
            <h3 class="mt-4 text-sm font-semibold text-[#181A1E]">Points for the doctor</h3>
            <ul class="mt-1 space-y-1 text-sm text-[#4E545F]">
                @foreach ($legacy['points'] as $point)
                    <li>{{ $point }}</li>
                @endforeach
            </ul>
        @endif
        @if (count($legacy['reviews']) > 0)
            <div class="mt-4 space-y-3">
                @foreach ($legacy['reviews'] as $report)
                    <article class="rounded-lg border border-[#E2E8F0] px-3 py-3">
                        @if ($report['group'])
                            <p class="text-sm font-semibold text-[#181A1E]">{{ $report['group'] }}</p>
                        @endif
                        @if ($report['file'] && $report['file'] !== $report['group'])
                            <p class="text-sm text-[#4E545F]">{{ $report['file'] }}</p>
                        @endif
                        @foreach ($report['urls'] as $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="mt-1 block break-all text-sm font-semibold text-[#0067FF]">{{ $url }}</a>
                        @endforeach
                        @foreach ($report['warnings'] as $warning)
                            <p class="mt-2 text-sm text-[#181A1E]">{{ $warning }}</p>
                        @endforeach
                        @if ($report['reading'])
                            <p class="mt-2 text-sm leading-6 text-[#4E545F]">{{ $report['reading'] }}</p>
                        @endif
                        @if ($report['attention'])
                            <p class="mt-2 text-sm font-semibold text-[#181A1E]">{{ $report['attention'] }}</p>
                        @endif
                        @if (count($report['findings']) > 0)
                            <div class="mt-2 overflow-x-auto">
                                <table class="w-full text-left text-sm">
                                    <tbody>
                                        @foreach ($report['findings'] as $finding)
                                            <tr>
                                                <td class="py-1 pr-3 font-semibold text-[#181A1E]">{{ $finding['name'] }}</td>
                                                <td class="py-1 pr-3 text-[#181A1E]">{{ trim($finding['value'].' '.$finding['unit']) }}</td>
                                                <td class="py-1 pr-3 text-[#4E545F]">{{ $finding['range'] !== '' ? $finding['range'] : '—' }}</td>
                                                <td class="py-1 text-[#4E545F]">{{ $finding['flag'] === 'unknown' ? 'Check' : ucfirst($finding['flag']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
        @if (count($legacy['recommendations']) > 0)
            <h3 class="mt-4 text-sm font-semibold text-[#181A1E]">Clinical recommendations</h3>
            <ol class="mt-1 list-decimal space-y-1 pl-5 text-sm text-[#4E545F]">
                @foreach ($legacy['recommendations'] as $recommendation)
                    <li>{{ $recommendation }}</li>
                @endforeach
            </ol>
        @endif
        @if (filled($legacy['brief']))
            <h3 class="mt-4 text-sm font-semibold text-[#181A1E]">Referral brief</h3>
            <p class="mt-1 text-sm leading-6 text-[#4E545F]">{{ $legacy['brief'] }}</p>
        @endif
    </section>
@endif
