@if ($rows === [])
    <p class="mt-4 text-sm text-[#64748B]">No consultation notes have been saved on these visits.</p>
@else
    <ul class="mt-2 divide-y divide-[#F1F5F9]">
        @foreach ($rows as $visit)
            <li class="py-3">
                <p class="text-sm font-semibold text-[#0067FF]">{{ $visit['date'] }}</p>
                @foreach ($visit['note_lines'] as $line)
                    <p class="mt-1 text-sm {{ $loop->first ? 'text-[#181A1E]' : 'text-[#4E545F]' }}">{{ $line }}</p>
                @endforeach
                <a href="{{ route('doctor.appointments.show', $visit['id']) }}#consultation" class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-[#0067FF] no-underline">
                    Open consultation
                    <x-ui.icon name="arrow" class="h-3.5 w-3.5" />
                </a>
            </li>
        @endforeach
    </ul>
@endif
