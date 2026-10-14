{{--
    Compact workspace title. Actions go in the default slot.
    <x-ui.page-header title="Appointments" description="Today’s visits.">
        <a href="..." class="btn-primary">Book</a>
    </x-ui.page-header>
--}}
@props(['title', 'description' => null, 'eyebrow' => null])

<header {{ $attributes->merge(['class' => 'mb-4 flex flex-wrap items-end justify-between gap-3']) }}>
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">{{ $eyebrow }}</p>
        @endif
        <h1 class="text-xl font-semibold tracking-tight text-heading sm:text-[22px]">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 max-w-3xl text-sm leading-5 text-body">{{ $description }}</p>
        @endif
    </div>
    @if (! $slot->isEmpty())
        <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</header>
