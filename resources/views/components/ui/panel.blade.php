{{--
    White section with an optional title row.
    <x-ui.panel title="Today’s schedule">
        <x-slot:action><a href="...">View all</a></x-slot:action>
        ...
    </x-ui.panel>
--}}
@props(['title' => null])

<section {{ $attributes->merge(['class' => 'panel']) }}>
    @if ($title)
        <div class="panel-head">
            <h2 class="text-sm font-semibold text-heading">{{ $title }}</h2>
            @isset($action)
                <div class="shrink-0 text-sm font-semibold">{{ $action }}</div>
            @endisset
        </div>
    @endif
    {{ $slot }}
</section>
