@php
    $value = function ($text) {
        return filled($text) ? $text : 'Not provided';
    };
@endphp

<section class="rounded-xl border border-[#E2E8F0] bg-white shadow-card">
    <div class="border-b border-[#E2E8F0] px-4 py-3">
        <h2 class="text-sm font-semibold text-[#181A1E]">Patient and appointment</h2>
    </div>
    <dl class="grid gap-3 px-4 py-3 text-sm sm:grid-cols-2">
        <div><dt class="text-xs text-[#64748B]">Name</dt><dd class="font-medium text-[#181A1E]">{{ $value($context['patient']['name']) }}</dd></div>
        <div><dt class="text-xs text-[#64748B]">Patient ID</dt><dd class="font-medium text-[#181A1E]">{{ $value($context['patient']['reference']) }}</dd></div>
        <div><dt class="text-xs text-[#64748B]">Age</dt><dd class="text-[#181A1E]">{{ $context['patient']['age'] ?: 'Not provided' }}</dd></div>
        <div><dt class="text-xs text-[#64748B]">Gender</dt><dd class="text-[#181A1E]">{{ $value($context['patient']['gender']) }}</dd></div>
        <div><dt class="text-xs text-[#64748B]">Blood type</dt><dd class="text-[#181A1E]">{{ $value($context['patient']['blood_type']) }}</dd></div>
        <div><dt class="text-xs text-[#64748B]">Visit</dt><dd class="text-[#181A1E]">{{ $context['appointment']['date'] ?: 'Not provided' }} · {{ $context['appointment']['status'] ?: 'Not provided' }}</dd></div>
        <div class="sm:col-span-2"><dt class="text-xs text-[#64748B]">Reason</dt><dd class="whitespace-pre-line text-[#181A1E]">{{ $value($context['appointment']['reason']) }}</dd></div>
        <div class="sm:col-span-2"><dt class="text-xs text-[#64748B]">Symptoms</dt><dd class="text-[#181A1E]">{{ $value($context['symptoms']) }}</dd></div>
        <div><dt class="text-xs text-[#64748B]">Duration</dt><dd class="text-[#181A1E]">Not provided</dd></div>
    </dl>
</section>

<section class="rounded-xl border border-[#E2E8F0] bg-white shadow-card">
    <div class="border-b border-[#E2E8F0] px-4 py-3">
        <h2 class="text-sm font-semibold text-[#181A1E]">Medical history</h2>
    </div>
    <div class="grid gap-4 px-4 py-3 lg:grid-cols-2">
        <dl class="space-y-2 text-sm">
            @foreach ($context['health'] as $item)
                <div class="flex justify-between gap-3">
                    <dt class="text-[#64748B]">{{ $item['label'] }}</dt>
                    <dd class="text-right text-[#181A1E]">{{ $item['value'] ?: 'Not provided' }}</dd>
                </div>
            @endforeach
            <div class="flex justify-between gap-3"><dt class="text-[#64748B]">Medications</dt><dd class="text-[#181A1E]">Not available</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-[#64748B]">Allergies</dt><dd class="text-[#181A1E]">Not available</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-[#64748B]">Recorded diagnosis</dt><dd class="text-right text-[#181A1E]">{{ $context['consultation']['diagnosis'] ?: 'Not available' }}</dd></div>
        </dl>
        <div class="text-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-[#64748B]">Earlier visits with you</p>
            @if (count($context['prior']) === 0)
                <p class="mt-2 text-[#4E545F]">Not available</p>
            @else
                <ul class="mt-2 space-y-2">
                    @foreach ($context['prior'] as $visit)
                        <li>
                            <span class="font-medium text-[#181A1E]">{{ $visit['date'] ?: 'Date not recorded' }}</span>
                            <span class="text-[#4E545F]"> · {{ $visit['reason'] ?: 'Reason not recorded' }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</section>

<section class="rounded-xl border border-[#E2E8F0] bg-white shadow-card">
    <div class="border-b border-[#E2E8F0] px-4 py-3">
        <h2 class="text-sm font-semibold text-[#181A1E]">Medical reports</h2>
    </div>
    @if (count($context['reports']) === 0)
        <p class="px-4 py-3 text-sm text-[#4E545F]">No reports were attached to this visit.</p>
    @else
        <ul class="divide-y divide-[#E2E8F0]">
            @foreach ($context['reports'] as $report)
                <li class="flex items-start gap-3 px-4 py-3">
                    @if ($selectable ?? false)
                        <input type="checkbox" name="report_ids[]" value="{{ $report['id'] }}" class="mt-1 rounded border-[#E2E8F0] text-[#0067FF] focus:ring-[#0067FF]" @checked($report['supported'] && (old('report_ids') === null || in_array((int) $report['id'], array_map('intval', (array) old('report_ids', [])), true))) @disabled(! $report['supported'])>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-[#181A1E]">{{ $report['name'] }}</p>
                        <p class="text-xs text-[#4E545F]">
                            {{ $report['type'] }}
                            · Report date not available
                            · Uploaded {{ $report['uploaded'] ?: 'date not recorded' }}
                            · {{ $report['supported'] ? 'Supported' : 'Unsupported file type' }}
                        </p>
                    </div>
                    <a href="{{ $report['url'] }}" target="_blank" rel="noopener" class="shrink-0 text-xs font-semibold text-[#0067FF] no-underline">Preview</a>
                </li>
            @endforeach
        </ul>
    @endif
</section>
