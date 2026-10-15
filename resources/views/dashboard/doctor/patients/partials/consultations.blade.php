@if ($rows === [])
    <p class="{{ ($cards ?? false) ? 'px-4 py-8 sm:px-5' : 'mt-3' }} text-sm text-[#64748B]">No consultation notes have been saved on these visits.</p>
@elseif ($cards ?? false)
    <ul class="space-y-3 p-3 sm:p-4">
        @foreach ($rows as $visit)
            @php
                $summary = collect($visit['note_lines'] ?? [])
                    ->map(fn ($line) => trim((string) preg_replace('/\s+/u', ' ', (string) $line)))
                    ->filter()
                    ->implode(' ');
                $visitLabel = $visit['visit'] ?? 'Consultation';
                $title = match ($visitLabel) {
                    'Follow-up' => 'Follow-up consultation',
                    'Emergency' => 'Emergency visit',
                    default => $visitLabel,
                };
                $kind = match ($visitLabel) {
                    'Follow-up' => ['label' => 'Follow-up', 'class' => 'bg-[#ECFDF3] text-[#15803D]', 'icon' => 'refresh'],
                    'Emergency' => ['label' => 'Emergency', 'class' => 'bg-[#FEF2F2] text-[#B91C1C]', 'icon' => 'alert'],
                    'Check-up' => ['label' => 'Check-up', 'class' => 'bg-[#FFFBEB] text-[#B45309]', 'icon' => 'clipboard'],
                    default => ['label' => $visitLabel, 'class' => 'bg-[#F3E8FF] text-[#7C3AED]', 'icon' => 'stethoscope'],
                };
                $weekday = \Illuminate\Support\Carbon::parse($visit['date'])->format('l');
                $chartUrl = route('doctor.appointments.show', $visit['id']).'#consultation';
            @endphp
            <li class="rounded-xl border border-[#E7EEF6] bg-white" x-data="{ open: false }">
                <div class="flex flex-col gap-3 px-4 py-3 lg:flex-row lg:items-center lg:gap-5">
                    <div class="flex w-full shrink-0 items-center gap-3 lg:w-[168px]">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#E8F1FF] text-[#0067FF]">
                            <x-ui.icon name="calendar" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 leading-tight">
                            <p class="text-sm font-semibold text-[#181A1E]">{{ $visit['date'] }}</p>
                            <p class="mt-0.5 text-xs text-[#64748B]">{{ $weekday }}</p>
                            @if (filled($visit['time'] ?? null))
                                <p class="text-xs text-[#64748B]">{{ $visit['time'] }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-[#181A1E]">{{ $title }}</p>
                        @if ($summary !== '')
                            <p class="mt-0.5 truncate text-sm text-[#64748B]" title="{{ $summary }}">{{ $summary }}</p>
                        @endif
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <span class="inline-flex items-center gap-1 rounded-full bg-[#F4F7FB] px-2 py-0.5 text-[11px] font-semibold text-[#475569]">
                                <x-ui.icon name="note" class="h-3 w-3" />
                                Consultation Note
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $kind['class'] }}">
                                <x-ui.icon :name="$kind['icon']" class="h-3 w-3" />
                                {{ $kind['label'] }}
                            </span>
                        </div>
                    </div>

                    <div class="flex min-w-0 items-center gap-2 lg:w-[180px] lg:shrink-0">
                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#F4F7FB] text-[#64748B]">
                            <x-ui.icon name="user" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0 leading-tight">
                            <p class="truncate text-sm font-medium text-[#181A1E]">{{ $clinician['name'] ?? 'Doctor' }}</p>
                            @if (filled($clinician['specialty'] ?? null))
                                <p class="truncate text-xs text-[#64748B]">{{ $clinician['specialty'] }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-1.5">
                        <a href="{{ $chartUrl }}" class="inline-flex h-9 items-center gap-1 rounded-lg border border-[#0067FF] bg-white px-3 text-sm font-semibold text-[#0067FF] no-underline hover:bg-[#F4F8FF]">
                            View details
                            <x-ui.icon name="arrow" class="h-3.5 w-3.5" />
                        </a>
                        <div class="relative">
                            <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-[#64748B] hover:bg-[#F8FAFC]" @click="open = !open" aria-label="Consultation actions" :aria-expanded="open">
                                <x-ui.icon name="dots" class="h-4 w-4" />
                            </button>
                            <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-20 mt-1 w-44 overflow-hidden rounded-xl border border-[#E7EEF6] bg-white py-1 text-left shadow-lift">
                                <a href="{{ $chartUrl }}" class="block px-3 py-2 text-sm font-medium text-[#181A1E] no-underline hover:bg-[#F8FAFC]">Open chart</a>
                                @if (! empty($visit['can_act']))
                                    <button type="button" class="block w-full px-3 py-2 text-left text-sm font-medium text-[#181A1E] hover:bg-[#F8FAFC]" @click="open = false; $dispatch('chart-action', { id: '{{ $visit['id'] }}', name: 'note' })">Add note</button>
                                    <button type="button" class="block w-full px-3 py-2 text-left text-sm font-medium text-[#181A1E] hover:bg-[#F8FAFC]" @click="open = false; $dispatch('chart-action', { id: '{{ $visit['id'] }}', name: 'follow' })">Schedule follow-up</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </li>
        @endforeach
    </ul>
@else
    <ul class="mt-1 divide-y divide-[#F1F5F9]">
        @foreach ($rows as $visit)
            @php
                $summary = collect($visit['note_lines'] ?? [])
                    ->map(fn ($line) => trim((string) preg_replace('/\s+/u', ' ', (string) $line)))
                    ->filter()
                    ->implode(' · ');
            @endphp
            <li>
                <a
                    href="{{ route('doctor.appointments.show', $visit['id']) }}#consultation"
                    class="-mx-2 grid grid-cols-[6.75rem_minmax(0,1fr)_auto] items-center gap-x-3 rounded-lg px-2 py-2 no-underline hover:bg-[#F8FAFC]"
                >
                    <span class="whitespace-nowrap text-sm font-semibold text-[#181A1E]">{{ $visit['date'] }}</span>
                    <span class="truncate text-sm text-[#4E545F]" title="{{ $summary }}">{{ $summary }}</span>
                    <span class="inline-flex items-center gap-1 whitespace-nowrap text-sm font-semibold text-[#0067FF]">
                        Open
                        <x-ui.icon name="arrow" class="h-3.5 w-3.5" />
                    </span>
                </a>
            </li>
        @endforeach
    </ul>
@endif
