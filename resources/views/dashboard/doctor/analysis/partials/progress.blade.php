@php
    $steps = [
        'preparing' => 'Reading the chart',
        'processing_reports' => 'Reviewing reports',
        'generating' => 'Writing the brief',
        'validating' => 'Preparing the review',
    ];
    $order = array_keys($steps);
    $currentIndex = array_search($analysis->progress, $order, true);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
    $width = (int) round((($currentIndex + 1) / count($steps)) * 100);
    $visit = array_filter([
        $context['appointment']['date'] ?? null,
        $context['appointment']['reason'] ?? null,
    ]);
@endphp

<section class="overflow-hidden rounded-2xl border border-[#E2E8F0] bg-white shadow-card" aria-live="polite">
    <div class="bg-gradient-to-br from-[#F4F8FF] to-white px-6 pb-5 pt-6">
        <div class="flex items-center gap-3">
            <x-ui.avatar :name="$patientName" :photo="$appointment->patient?->photo" size="lg" />
            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-[#0067FF]">AI Clinical Analysis</p>
                <h1 class="mt-0.5 truncate text-lg font-semibold tracking-tight text-[#181A1E]">{{ $patientName }}</h1>
                @if (count($visit) > 0)
                    <p class="mt-0.5 truncate text-sm text-[#64748B]">{{ implode(' · ', $visit) }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="px-6 pb-6">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold text-[#181A1E]">Preparing the brief</h2>
                <p class="mt-1 text-sm text-[#64748B]">Reviewing the chart and selected reports.</p>
            </div>
            <p class="shrink-0 text-sm font-semibold text-[#0067FF]">{{ $currentIndex + 1 }} of {{ count($steps) }}</p>
        </div>

        <div class="mt-4 h-2 overflow-hidden rounded-full bg-[#E8F2FF]" role="progressbar" aria-valuenow="{{ $width }}" aria-valuemin="0" aria-valuemax="100">
            <div class="h-full rounded-full bg-[#0067FF] transition-all" style="width: {{ $width }}%"></div>
        </div>

        <ol class="mt-6">
            @foreach ($steps as $key => $label)
                @php
                    $index = array_search($key, $order, true);
                    $done = $index < $currentIndex;
                    $active = $index === $currentIndex;
                @endphp
                <li class="relative flex gap-3 {{ $loop->last ? '' : 'pb-5' }}">
                    @unless ($loop->last)
                        <span class="absolute left-4 top-8 h-[calc(100%-1.25rem)] w-px bg-[#E2E8F0]" aria-hidden="true"></span>
                    @endunless
                    <span class="relative z-10 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold {{ $done ? 'bg-[#ECFDF3] text-[#15803D]' : ($active ? 'bg-[#0067FF] text-white ring-4 ring-[#E8F2FF]' : 'bg-[#F8FAFC] text-[#94A3B8]') }}">
                        @if ($done)
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.2 7.2a1 1 0 0 1-1.4 0L3.3 9.1a1 1 0 1 1 1.4-1.4l4.1 4.1 6.5-6.5a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                        @else
                            {{ $loop->iteration }}
                        @endif
                        @if ($active)
                            <span class="absolute inset-0 animate-ping rounded-full bg-[#0067FF]/25" aria-hidden="true"></span>
                        @endif
                    </span>
                    <div class="min-w-0 pt-1">
                        <p class="text-sm {{ $active ? 'font-semibold text-[#181A1E]' : ($done ? 'font-medium text-[#181A1E]' : 'text-[#94A3B8]') }}">{{ $label }}</p>
                        @if ($done)
                            <p class="text-xs text-[#15803D]">Done</p>
                        @elseif ($active)
                            <p class="text-xs font-medium text-[#0067FF]">In progress</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>

        <p class="mt-6 border-t border-[#E2E8F0] pt-4 text-xs leading-5 text-[#64748B]">The brief appears here when it is ready. Nothing is written into the visit note.</p>
    </div>
</section>
