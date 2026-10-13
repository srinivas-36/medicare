{{--
    Section title. level is h2 on the homepage and can be h1 on a standalone page.
    <x-section-heading title="How it works" subtitle="Four steps." />
--}}
@props(['title', 'subtitle' => null, 'level' => 'h2', 'align' => 'center'])

<div @class(['text-center' => $align === 'center', 'text-left' => $align === 'left'])>
    <{{ $level }} class="text-3xl font-bold tracking-tight text-heading md:text-4xl md:leading-[1.15]">
        {{ $title }}
    </{{ $level }}>

    @if ($subtitle)
        <p class="mt-3 max-w-2xl text-base leading-7 text-text {{ $align === 'center' ? 'mx-auto' : '' }}">
            {{ $subtitle }}
        </p>
    @endif
</div>
