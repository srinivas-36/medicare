{{--
    Top band for a public page that is not the homepage.
    Optional slot is for a search form or other controls under the subtitle.
--}}
@props(['eyebrow' => null, 'title', 'subtitle' => null])

<section class="border-b border-line bg-light">
    <div class="page py-12 text-center lg:py-16">
        @if ($eyebrow)
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary">{{ $eyebrow }}</p>
        @endif

        <h1 class="text-4xl font-bold tracking-tight text-heading md:text-5xl {{ $eyebrow ? 'mt-3' : '' }}">
            {{ $title }}
        </h1>

        @if ($subtitle)
            <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-text">
                {{ $subtitle }}
            </p>
        @endif

        @if (! $slot->isEmpty())
            <div class="mt-8">
                {{ $slot }}
            </div>
        @endif
    </div>
</section>
