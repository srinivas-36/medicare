<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') — Medicare</title>
    @include('partials.fonts')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-dvh overflow-hidden bg-page font-sans text-body" x-data="{ navOpen: false }">
    <div class="flex h-dvh">
        @include('components.dashboard.sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 shrink-0 items-center justify-between gap-4 border-b border-line bg-white px-4 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-line text-navy lg:hidden"
                        @click="navOpen = true"
                        aria-label="Open menu"
                    >
                        <x-ui.icon name="menu" class="h-5 w-5" />
                    </button>
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted">Medicare</p>
                        <p class="truncate text-sm font-semibold text-navy">Clinical workspace</p>
                    </div>
                </div>

                @include('components.dashboard.account-menu')
            </header>

            <main class="min-h-0 flex-1 overflow-y-auto">
                <div class="mx-auto w-full max-w-[1180px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
