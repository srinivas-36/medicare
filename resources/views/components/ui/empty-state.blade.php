{{--
    Shown when a list has no rows.
    <x-ui.empty-state title="No doctors yet" text="Approved profiles will appear here.">
        <a href="..." class="btn-primary">Browse</a>
    </x-ui.empty-state>
--}}
@props(['title', 'text' => null])

<div {{ $attributes->merge(['class' => 'card mx-auto max-w-xl px-5 py-8 text-center']) }}>
    <h2 class="text-base font-semibold text-heading">{{ $title }}</h2>
    @if ($text)
        <p class="mt-2 text-sm leading-6 text-text">{{ $text }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>
