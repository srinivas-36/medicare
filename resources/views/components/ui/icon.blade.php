{{--
    Small outline icons. Pass the name you need:
    <x-ui.icon name="search" class="h-5 w-5" />
    Add a new @case here when a page needs another icon.
--}}
@props(['name' => 'search'])

<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
    @switch($name)
        @case('search')
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.5-3.5" stroke-linecap="round" />
            @break
        @case('pin')
            <path d="M12 21s7-5.4 7-11a7 7 0 1 0-14 0c0 5.6 7 11 7 11z" stroke-linejoin="round" />
            <circle cx="12" cy="10" r="2.5" />
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M3 10h18M8 3v4M16 3v4" stroke-linecap="round" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v6l4 2" stroke-linecap="round" stroke-linejoin="round" />
            @break
        @case('user')
            <circle cx="12" cy="8" r="3.5" />
            <path d="M5 19a7 7 0 0 1 14 0" stroke-linecap="round" />
            @break
        @case('stethoscope')
            <path d="M6 3v6a4 4 0 0 0 8 0V3" stroke-linecap="round" />
            <path d="M6 8H4a2 2 0 0 0-2 2v1a6 6 0 0 0 6 6h2" stroke-linecap="round" />
            <circle cx="17" cy="16" r="3" />
            @break
        @case('heart')
            <path d="M19 14c1.5-1.5 3-3.2 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.8 0-3 .5-4.5 2C10.5 3.5 9.3 3 7.5 3A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4 3 5.5l7 7Z" stroke-linecap="round" stroke-linejoin="round" />
            @break
        @case('shield')
            <path d="M12 3 5 6v6c0 4.2 2.8 7.2 7 8.5 4.2-1.3 7-4.3 7-8.5V6l-7-3Z" stroke-linejoin="round" />
            @break
        @case('card')
            <rect x="3" y="6" width="18" height="13" rx="2" />
            <path d="M3 10h18" stroke-linecap="round" />
            @break
        @case('file')
            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z" stroke-linejoin="round" />
            <path d="M14 3v5h5M8 13h8M8 17h6" stroke-linecap="round" />
            @break
        @case('download')
            <path d="M12 4v10" stroke-linecap="round" />
            <path d="m8 10 4 4 4-4" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M5 19h14" stroke-linecap="round" />
            @break
        @case('check')
            <circle cx="12" cy="12" r="9" />
            <path d="m8 12 2.5 2.5L16 9" stroke-linecap="round" stroke-linejoin="round" />
            @break
        @case('arrow')
            <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
            @break
        @case('grid')
            <rect x="4" y="4" width="6" height="6" rx="1" />
            <rect x="14" y="4" width="6" height="6" rx="1" />
            <rect x="4" y="14" width="6" height="6" rx="1" />
            <rect x="14" y="14" width="6" height="6" rx="1" />
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14" stroke-linecap="round" />
            @break
        @case('chevron')
            <path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round" />
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round" />
            @break
        @case('close')
            <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" />
            @break
    @endswitch
</svg>
