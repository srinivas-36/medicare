@php
$navLinks = [
['route' => 'home', 'label' => 'Home'],
['route' => 'doctors.index', 'label' => 'Find a Doctor'],
['route' => 'services.index', 'label' => 'Services'],
['route' => 'contact', 'label' => 'Contact Us'],
];
@endphp

<header class="sticky top-0 left-0 z-[99999] h-[90px] w-full bg-[#e7f6fb] bg-[url('/images/mask.png')] bg-cover bg-center bg-no-repeat">
    <input id="nav-toggle" type="checkbox" class="peer hidden">

    <div class="relative mx-auto flex h-[100px] w-full max-w-[1440px] items-center justify-between px-5">
        <a href="{{ route('home') }}">
            <img src="{{ asset('images/logo.png') }}" alt="Medicare" class="block h-[52px] w-auto">
        </a>

        <ul class="absolute left-1/2 top-1/2 m-0 flex -translate-x-1/2 -translate-y-1/2 list-none items-center gap-[43px] p-0 max-[768px]:hidden">
            @foreach ($navLinks as $link)
            <li>
                <a
                    href="{{ route($link['route']) }}"
                    @class([ 'text-base font-semibold leading-7 no-underline hover:text-[#0067FF]' , 'text-[#0067FF]'=> request()->routeIs($link['route']),
                    'text-[#4E545F]' => ! request()->routeIs($link['route']),
                    ])
                    >
                    {{ $link['label'] }}
                </a>
            </li>
            @endforeach
        </ul>

        <div class="flex items-center gap-4">
            <a href="{{ route('login') }}" class="inline-flex h-11 items-center justify-center rounded-full bg-[#0067FF] px-6 text-base font-semibold text-white no-underline">
                Login
            </a>

            <label for="nav-toggle" class="hidden h-6 w-6 cursor-pointer text-[#181A1E] max-[768px]:block" aria-label="Open menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-6 w-6">
                    <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                </svg>
            </label>
        </div>
    </div>

    <div class="hidden max-[768px]:peer-checked:fixed max-[768px]:peer-checked:inset-0 max-[768px]:peer-checked:z-[100] max-[768px]:peer-checked:block max-[768px]:peer-checked:bg-black/[0.52]">
        <label for="nav-toggle" class="absolute inset-0 cursor-pointer"></label>
        <ul class="absolute right-0 top-0 m-0 flex h-full w-60 list-none flex-col items-center justify-center gap-6 bg-white p-0">
            @foreach ($navLinks as $link)
            <li>
                <a
                    href="{{ route($link['route']) }}"
                    @class([ 'text-base font-semibold leading-7 no-underline hover:text-[#0067FF]' , 'text-[#0067FF]'=> request()->routeIs($link['route']),
                    'text-[#4E545F]' => ! request()->routeIs($link['route']),
                    ])
                    >
                    {{ $link['label'] }}
                </a>
            </li>
            @endforeach
        </ul>
    </div>
</header>