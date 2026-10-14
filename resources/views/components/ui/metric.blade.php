{{--
    Compact count card. The value must come from the controller.
    <x-ui.metric label="Today" :value="$counts['today']" tone="blue" icon="calendar" />
--}}
@props(['label', 'value', 'tone' => 'blue', 'href' => null, 'icon' => null])

@php
    $tones = [
        'blue' => ['text' => 'text-[#0067FF]', 'chip' => 'bg-[#E8F2FF] text-[#0067FF]'],
        'amber' => ['text' => 'text-[#D97706]', 'chip' => 'bg-[#FFF7ED] text-[#D97706]'],
        'green' => ['text' => 'text-[#15803D]', 'chip' => 'bg-[#F0FDF4] text-[#15803D]'],
        'red' => ['text' => 'text-[#DC2626]', 'chip' => 'bg-[#FEF2F2] text-[#DC2626]'],
        'slate' => ['text' => 'text-heading', 'chip' => 'bg-[#F8FAFC] text-[#64748B]'],
    ];
    $toneSet = $tones[$tone] ?? $tones['blue'];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'metric flex items-start justify-between gap-3 no-underline transition hover:border-primary/40']) }}>
        <span class="min-w-0">
            <p class="metric-label">{{ $label }}</p>
            <p class="metric-value {{ $toneSet['text'] }}">{{ $value }}</p>
        </span>
        @if ($icon)
            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $toneSet['chip'] }}">
                <x-ui.icon :name="$icon" class="h-4 w-4" />
            </span>
        @endif
    </a>
@else
    <article {{ $attributes->merge(['class' => 'metric flex items-start justify-between gap-3']) }}>
        <span class="min-w-0">
            <p class="metric-label">{{ $label }}</p>
            <p class="metric-value {{ $toneSet['text'] }}">{{ $value }}</p>
        </span>
        @if ($icon)
            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $toneSet['chip'] }}">
                <x-ui.icon :name="$icon" class="h-4 w-4" />
            </span>
        @endif
    </article>
@endif
