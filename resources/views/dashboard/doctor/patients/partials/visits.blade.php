@if ($rows === [])
    <p class="mt-4 text-sm text-[#64748B]">No visits with you yet.</p>
@else
    <ul class="mt-2 divide-y divide-[#F1F5F9]">
        @foreach ($rows as $visit)
            <li class="flex items-start gap-3 py-3">
                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-[#0067FF]" aria-hidden="true"></span>
                <div class="w-[108px] shrink-0">
                    <a href="{{ route('doctor.appointments.show', $visit['id']) }}" class="text-sm font-semibold text-[#181A1E] no-underline hover:text-[#0067FF]">{{ $visit['date'] }}</a>
                    <p class="text-xs text-[#64748B]">{{ $visit['time'] }}</p>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-[#181A1E]">{{ $visit['visit'] ?? 'Visit' }}</p>
                    @if (filled($visit['complaint'] ?? null))
                        <p class="text-sm text-[#4E545F]">{{ $visit['complaint'] }}</p>
                    @endif
                    @foreach ($visit['extras'] ?? [] as $extra)
                        <p class="text-xs text-[#64748B]">{{ $extra }}</p>
                    @endforeach
                </div>
                <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass[$visit['status']] ?? 'bg-[#E8F2FF] text-[#1D4ED8]' }}">{{ $visit['status'] }}</span>
            </li>
        @endforeach
    </ul>
@endif
