{{--
    Public navigation.
    $navLinks uses route names that already exist in routes/web.php.
    Guests see Register and Log in. Signed-in people see Dashboard and Log out.
--}}
@php
    $navLinks = [
        ['route' => 'home', 'label' => 'Home'],
        ['route' => 'doctors.index', 'label' => 'Find a Doctor'],
        ['route' => 'services.index', 'label' => 'Services'],
        ['route' => 'about', 'label' => 'About'],
        ['route' => 'contact', 'label' => 'Contact'],
    ];
@endphp

<header class="sticky top-0 z-50 border-b border-line bg-white">
    <input id="nav-toggle" type="checkbox" class="peer hidden">

    <div class="page flex h-[72px] items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="shrink-0">
            <img src="{{ asset('images/logo.png') }}" alt="Medicare" class="block h-10 w-auto">
        </a>

        <ul class="absolute left-1/2 hidden -translate-x-1/2 list-none items-center gap-1 p-0 lg:flex">
            @foreach ($navLinks as $link)
                <li>
                    <a
                        href="{{ route($link['route']) }}"
                        @if (request()->routeIs($link['route'])) aria-current="page" @endif
                        @class([
                            'inline-flex h-10 items-center rounded-lg px-3 text-[15px] font-semibold no-underline',
                            'text-primary' => request()->routeIs($link['route']),
                            'text-navy hover:text-primary' => ! request()->routeIs($link['route']),
                        ])
                    >
                        {{ $link['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="flex items-center gap-2">
            @guest
                <a href="{{ route('register') }}" class="btn-secondary hidden h-11 px-4 sm:inline-flex">Register</a>
                <a href="{{ route('login') }}" class="btn-primary h-11 px-4">Log in</a>
            @endguest

            @auth
                <a href="{{ route('dashboard') }}" class="btn-primary h-11 px-4">Dashboard</a>
                <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                    @csrf
                    <button type="submit" class="btn-ghost h-11 px-3">Log out</button>
                </form>
            @endauth

            <label for="nav-toggle" class="inline-flex h-11 w-11 cursor-pointer items-center justify-center rounded-xl border border-line text-navy lg:hidden" aria-label="Open menu">
                <x-ui.icon name="menu" />
            </label>
        </div>
    </div>

    <div class="hidden peer-checked:fixed peer-checked:inset-0 peer-checked:z-[60] peer-checked:block peer-checked:bg-navy/40 lg:hidden">
        <label for="nav-toggle" class="absolute inset-0 cursor-pointer" aria-label="Close menu"></label>
        <div class="absolute right-0 top-0 flex h-full w-[min(100%,20rem)] flex-col bg-white p-6 shadow-lift">
            <div class="mb-8 flex items-center justify-between">
                <img src="{{ asset('images/logo.png') }}" alt="Medicare" class="h-9 w-auto">
                <label for="nav-toggle" class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-line" aria-label="Close menu">
                    <x-ui.icon name="close" />
                </label>
            </div>
            <ul class="m-0 list-none space-y-1 p-0">
                @foreach ($navLinks as $link)
                    <li>
                        <a
                            href="{{ route($link['route']) }}"
                            @class([
                                'flex h-12 items-center rounded-xl px-4 text-base font-semibold no-underline',
                                'bg-light text-primary' => request()->routeIs($link['route']),
                                'text-navy hover:bg-page' => ! request()->routeIs($link['route']),
                            ])
                        >
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-auto space-y-3 pt-8">
                @guest
                    <a href="{{ route('register') }}" class="btn-secondary w-full">Register</a>
                    <a href="{{ route('login') }}" class="btn-primary w-full">Log in</a>
                @endguest
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-primary w-full">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-ghost w-full">Log out</button>
                    </form>
                @endauth
            </div>
        </div>
    </div>
</header>
