@if ($rows === [])
    <p class="mt-4 text-sm text-[#64748B]">No reports are attached to these visits.</p>
@else
    <ul class="mt-2 divide-y divide-[#F1F5F9]">
        @foreach ($rows as $visit)
            <li class="flex items-center gap-3 py-2.5">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#F4F7FB] text-[#64748B]">
                    <x-ui.icon name="file" class="h-4 w-4" />
                </span>
                <span class="min-w-0 flex-1 text-sm text-[#181A1E]">{{ $visit['date'] }} · {{ $visit['reports'] }} {{ \Illuminate\Support\Str::plural('file', $visit['reports']) }}</span>
                <button type="button" class="shrink-0 text-sm font-semibold text-[#0067FF]" @click="openReports(@js($visit['report_files'] ?? []), @js($visit['date'].' reports'))">Open</button>
            </li>
        @endforeach
    </ul>
@endif
