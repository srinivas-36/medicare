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
        @case('users')
            <circle cx="9" cy="8" r="3" />
            <path d="M3.5 19a5.5 5.5 0 0 1 11 0" stroke-linecap="round" />
            <circle cx="17" cy="9" r="2.25" />
            <path d="M16 19a4.5 4.5 0 0 1 5-4.4" stroke-linecap="round" />
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
        @case('trash')
            <path d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M10 11v6M14 11v6" stroke-linecap="round" />
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
        @case('logout')
            <path d="M9 6H6a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3" stroke-linecap="round" />
            <path d="M13 16l4-4-4-4M17 12H9" stroke-linecap="round" stroke-linejoin="round" />
            @break
        @case('home')
            <path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z" stroke-linejoin="round" />
            @break
        @case('star')
            <path d="m12 3.5 2.2 4.6 5 .7-3.6 3.5.9 5.1L12 15.8 7.5 17.4l.9-5.1L4.8 8.8l5-.7L12 3.5Z" fill="currentColor" stroke-linejoin="round" />
            @break
        @case('globe')
            <circle cx="12" cy="12" r="9" />
            <path d="M3 12h18M12 3c2.4 2.7 3.6 5.7 3.6 9s-1.2 6.3-3.6 9c-2.4-2.7-3.6-5.7-3.6-9s1.2-6.3 3.6-9Z" />
            @break
        @case('upload')
            <path d="M7 18h10a4 4 0 0 0 .5-8 5 5 0 0 0-9.6-1.5A3.5 3.5 0 0 0 7 18Z" stroke-linejoin="round" />
            <path d="M12 16V10M9.5 12.5 12 10l2.5 2.5" stroke-linecap="round" stroke-linejoin="round" />
            @break
        @case('image')
            <rect x="4" y="5" width="16" height="14" rx="2" />
            <circle cx="9" cy="10" r="1.4" />
            <path d="m4 16 4.2-3.6 3.2 2.6 2.4-2 6.2 5" stroke-linejoin="round" />
            @break
        @case('message')
            <path d="M5 6h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H9l-4 3v-3H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Z" stroke-linejoin="round" />
            <path d="M8 10h8M8 13h5" stroke-linecap="round" />
            @break
        @case('send')
            <path d="M4 12 20 4l-6 16-2.5-6.5L4 12Z" stroke-linejoin="round" />
            <path d="m11.5 13.5 8.5-9.5" stroke-linecap="round" />
            @break
        @case('bell')
            <path d="M6 9a6 6 0 1 1 12 0c0 7 3 7 3 9H3c0-2 3-2 3-9Z" stroke-linejoin="round" />
            <path d="M10 20a2 2 0 0 0 4 0" stroke-linecap="round" />
            @break
        @case('dots')
            <circle cx="12" cy="5" r="1.2" fill="currentColor" stroke="none" />
            <circle cx="12" cy="12" r="1.2" fill="currentColor" stroke="none" />
            <circle cx="12" cy="19" r="1.2" fill="currentColor" stroke="none" />
            @break
        @case('chevron-left')
            <path d="m14 6-6 6 6 6" stroke-linecap="round" stroke-linejoin="round" />
            @break
        @case('chevron-right')
            <path d="m10 6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
            @break
        @case('bolt')
            <path d="M13 3 5 14h7l-1 7 8-11h-7l1-7Z" stroke-linejoin="round" />
            @break
        @case('chart')
            <path d="M4 19V5M4 19h16" stroke-linecap="round" />
            <path d="M8 16v-5M12 16V8M16 16v-3" stroke-linecap="round" />
            @break
        @case('refresh')
            <path d="M20 12a8 8 0 1 1-2.3-5.6" stroke-linecap="round" />
            <path d="M20 4v5h-5" stroke-linecap="round" stroke-linejoin="round" />
            @break
        @case('headset')
            <path d="M4 13a8 8 0 0 1 16 0" stroke-linecap="round" />
            <path d="M4 13v4a2 2 0 0 0 2 2h1v-6H6a2 2 0 0 0-2 2ZM20 13v4a2 2 0 0 1-2 2h-1v-6h1a2 2 0 0 1 2 2Z" stroke-linejoin="round" />
            @break
        @case('clipboard')
            <rect x="6" y="4" width="12" height="16" rx="2" />
            <path d="M9 4.5h6V6a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1V4.5Z" stroke-linejoin="round" />
            <path d="M9 11h6M9 15h4" stroke-linecap="round" />
            @break
        @case('settings')
            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" stroke-linejoin="round" />
            <circle cx="12" cy="12" r="3" />
            @break
        @case('mail')
            <rect x="3" y="5" width="18" height="14" rx="2" />
            <path d="m4 7 8 6 8-6" stroke-linecap="round" stroke-linejoin="round" />
            @break
        @case('info')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 11v6M12 8h.01" stroke-linecap="round" />
            @break
        @case('thermometer')
            <path d="M10 13.5V5a2 2 0 1 1 4 0v8.5a3.5 3.5 0 1 1-4 0Z" stroke-linejoin="round" />
            <path d="M12 16.5V10" stroke-linecap="round" />
            @break
        @case('flask')
            <path d="M9 3h6M10 3v5.5L6 19a2 2 0 0 0 1.8 2.8h8.4A2 2 0 0 0 18 19l-4-10.5V3" stroke-linejoin="round" />
            <path d="M8.2 14h7.6" stroke-linecap="round" />
            @break
        @case('thyroid')
            <path d="M12 8c-1.5 2.2-3.6 3.4-6 3.2 1.4 3.2 3.8 4.8 6 5.3 2.2-.5 4.6-2.1 6-5.3-2.4.2-4.5-1-6-3.2Z" stroke-linejoin="round" />
            <path d="M12 4.5v3.2" stroke-linecap="round" />
            @break
        @case('phone')
            <path d="M8 3h3l1.2 3-1.8 1.1a12 12 0 0 0 5.5 5.5L17 11l3 1.2V15a2 2 0 0 1-2.2 2A15 15 0 0 1 5 6.2 2 2 0 0 1 7 4h1Z" stroke-linejoin="round" />
            @break
        @case('gender')
            <circle cx="10" cy="14" r="3.5" />
            <path d="m12.5 11.5 5-5M15 6.5h3V9.5" stroke-linecap="round" stroke-linejoin="round" />
            @break
        @case('pencil')
            <path d="M12 20h8" stroke-linecap="round" />
            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4 11.5-11.5Z" stroke-linejoin="round" />
            @break
        @case('video')
            <rect x="3" y="6" width="12" height="12" rx="2" />
            <path d="m15 10 6-3v10l-6-3" stroke-linejoin="round" />
            @break
        @case('spark')
            <path d="M12 3.5 13.4 8.6 18.5 10 13.4 11.4 12 16.5 10.6 11.4 5.5 10 10.6 8.6 12 3.5Z" stroke-linejoin="round" />
            <path d="M18 14.5 18.6 16.4 20.5 17 18.6 17.6 18 19.5 17.4 17.6 15.5 17 17.4 16.4 18 14.5Z" stroke-linejoin="round" />
            @break
        @case('droplet')
            <path d="M12 3s6 6.2 6 10.2a6 6 0 0 1-12 0C6 9.2 12 3 12 3Z" stroke-linejoin="round" />
            @break
        @case('activity')
            <path d="M3 12h4l2.5-6 4 12L16 12h5" stroke-linecap="round" stroke-linejoin="round" />
            @break
        @case('alert')
            <path d="M12 4 3.5 19h17L12 4Z" stroke-linejoin="round" />
            <path d="M12 10v4M12 16.5h.01" stroke-linecap="round" />
            @break
        @case('note')
            <path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z" stroke-linejoin="round" />
            <path d="M14 3v5h5M9 13h6M9 17h4" stroke-linecap="round" />
            @break
        @case('id')
            <rect x="3" y="5" width="18" height="14" rx="2" />
            <circle cx="8.5" cy="12" r="2" />
            <path d="M13 10h5M13 14h4" stroke-linecap="round" />
            @break
        @case('pill')
            <path d="M8.5 15.5a4 4 0 0 1 0-5.7l4.3-4.3a4 4 0 0 1 5.7 5.7l-4.3 4.3a4 4 0 0 1-5.7 0Z" stroke-linejoin="round" />
            <path d="m10.2 10.2 3.6 3.6" stroke-linecap="round" />
            @break
        @case('leaf')
            <path d="M5 19s1-8 7.5-12.5S20 4 20 4s-.5 7.5-5 12S5 19 5 19Z" stroke-linejoin="round" />
            <path d="M8 16c2-2 4.5-4 8-6" stroke-linecap="round" />
            @break
    @endswitch
</svg>
