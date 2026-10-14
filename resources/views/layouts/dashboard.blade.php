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
<body
    class="app-shell h-dvh overflow-hidden bg-[#F4F7FB] font-sans text-[#4E545F]"
    x-data="{
        navOpen: false,
        collapsed: false,
        init() {
            this.collapsed = window.localStorage.getItem('sidebar-collapsed') === '1';
        },
        toggleCollapsed() {
            this.collapsed = ! this.collapsed;
            window.localStorage.setItem('sidebar-collapsed', this.collapsed ? '1' : '0');
        }
    }"
    :class="{ 'sidebar-collapsed': collapsed }"
>
    @php
        $headerUser = auth()->user();
        $headerRole = $headerUser->role ?? 'patient';
        $workspace = config("dashboard.roles.{$headerRole}.workspace", 'Workspace');
        $unread = $headerUser->unreadNotifications()->count();
        [$searchAction, $searchPlaceholder] = match ($headerRole) {
            'doctor' => [route('doctor.patients'), 'Search patients, appointments, or anything...'],
            'receptionist' => [route('receptionist.patients'), 'Search patients'],
            'admin' => [route('admin.users'), 'Search users'],
            default => [route('patient.book'), 'Search doctors'],
        };
    @endphp
    <div class="flex h-dvh">
        @include('components.dashboard.sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 shrink-0 items-center gap-3 border-b border-[#E2E8F0] bg-white px-4 sm:px-5" @keydown.window.meta.k.prevent="($refs.shellSearch || document.getElementById('shell-search'))?.focus()" @keydown.window.ctrl.k.prevent="($refs.shellSearch || document.getElementById('shell-search'))?.focus()">
                <button
                    type="button"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-[#E2E8F0] text-[#181A1E] lg:hidden"
                    @click="navOpen = true"
                    aria-label="Open menu"
                >
                    <x-ui.icon name="menu" class="h-5 w-5" />
                </button>

                @if ($headerRole !== 'doctor')
                    <div class="hidden min-w-0 lg:block lg:w-[180px] lg:shrink-0">
                        <p class="truncate text-sm font-semibold text-[#181A1E]">{{ $workspace }}</p>
                        <p class="truncate text-xs text-[#64748B]">{{ now()->format('l, j M Y') }}</p>
                    </div>
                @endif

                @if ($headerRole === 'receptionist')
                    <div
                        class="relative min-w-0 flex-1"
                        x-data="patientLookup(@js(route('receptionist.patients.search')), @js(route('receptionist.appointments.create')), @js(route('receptionist.patients')))"
                        @click.outside="close()"
                    >
                        <form action="{{ $searchAction }}" method="GET" @submit.prevent="submit()">
                            <label for="shell-search" class="sr-only">{{ $searchPlaceholder }}</label>
                            <div class="relative max-w-xl">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[#94A3B8]">
                                    <x-ui.icon name="search" class="h-4 w-4" />
                                </span>
                                <input
                                    id="shell-search"
                                    x-ref="shellSearch"
                                    type="search"
                                    name="q"
                                    x-model="query"
                                    @input.debounce.300ms="lookup()"
                                    @focus="open = results.length > 0"
                                    @keydown.escape="close()"
                                    placeholder="{{ $searchPlaceholder }}"
                                    autocomplete="off"
                                    role="searchbox"
                                    :aria-busy="searching ? 'true' : 'false'"
                                    class="h-10 w-full rounded-lg border border-[#E2E8F0] bg-[#F8FAFC] pl-9 pr-16 text-sm text-[#181A1E] outline-none placeholder:text-[#94A3B8] focus:border-[#0067FF] focus:bg-white focus:ring-4 focus:ring-[#0067FF]/15"
                                >
                                <span x-show="searching" x-cloak class="absolute right-12 top-1/2 -translate-y-1/2 text-[#0067FF]" role="status" aria-label="Searching patients">
                                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.25" stroke-width="3"></circle>
                                        <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                    </svg>
                                </span>
                                <span x-show="!searching" class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 items-center rounded-md border border-[#E2E8F0] bg-white px-1.5 py-0.5 text-[11px] font-semibold text-[#94A3B8] sm:inline-flex" aria-hidden="true">⌘ K</span>
                            </div>
                        </form>
                        <div
                            x-show="open"
                            x-cloak
                            class="absolute left-0 right-0 z-30 mt-2 max-w-xl overflow-hidden rounded-xl border border-[#E2E8F0] bg-white shadow-lg"
                        >
                            <p x-show="searchError !== ''" x-text="searchError" class="px-4 py-3 text-sm text-red-600"></p>
                            <p x-show="searchError === '' && !searching && query.trim().length >= 2 && results.length === 0" class="px-4 py-3 text-sm text-[#64748B]">No patients match that search.</p>
                            <ul>
                                <template x-for="patient in results" :key="patient.id">
                                    <li>
                                        <button type="button" @click="book(patient)" class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-[#F7FAFF]">
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-semibold text-[#181A1E]" x-text="patient.name"></span>
                                                <span class="block truncate text-xs text-[#64748B]" x-text="[patient.email, patient.phone].filter(Boolean).join(' · ')"></span>
                                            </span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                @else
                    <form action="{{ $searchAction }}" method="GET" class="min-w-0 flex-1">
                        <label for="shell-search" class="sr-only">{{ $searchPlaceholder }}</label>
                        <div class="relative max-w-xl">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[#94A3B8]">
                                <x-ui.icon name="search" class="h-4 w-4" />
                            </span>
                            <input id="shell-search" x-ref="shellSearch" type="search" name="q" placeholder="{{ $searchPlaceholder }}" class="h-10 w-full rounded-lg border border-[#E2E8F0] bg-[#F8FAFC] pl-9 {{ $headerRole === 'doctor' ? 'pr-4' : 'pr-16' }} text-sm text-[#181A1E] outline-none placeholder:text-[#94A3B8] focus:border-[#0067FF] focus:bg-white focus:ring-4 focus:ring-[#0067FF]/15">
                            @if ($headerRole !== 'doctor')
                                <span class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 items-center rounded-md border border-[#E2E8F0] bg-white px-1.5 py-0.5 text-[11px] font-semibold text-[#94A3B8] sm:inline-flex" aria-hidden="true">⌘ K</span>
                            @endif
                        </div>
                    </form>
                @endif

                @if ($headerRole === 'doctor')
                    <div class="ml-auto hidden items-center lg:flex">
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-[#181A1E]">
                            <x-ui.icon name="calendar" class="h-4 w-4 text-[#64748B]" />
                            <time datetime="{{ now()->toDateString() }}">{{ now()->format('l, j M Y') }}</time>
                        </span>
                    </div>
                @endif

                <div class="{{ $headerRole === 'doctor' ? '' : 'ml-auto' }} flex items-center gap-2">
                    <a href="{{ route('account.notifications') }}" class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg text-[#64748B] hover:bg-[#F7FAFF] hover:text-[#181A1E]" aria-label="Notifications">
                        <x-ui.icon name="bell" class="h-4 w-4" />
                        @if ($unread > 0)
                            <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-[#EF4444] ring-2 ring-white"></span>
                        @endif
                    </a>

                    @include('components.dashboard.account-menu')
                </div>
            </header>

            <main class="min-h-0 flex-1 overflow-y-auto bg-[#F4F7FB]">
                <div class="mx-auto w-full max-w-[1440px] px-4 py-4 sm:px-6 lg:px-8 lg:py-6">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @if (($headerRole ?? null) === 'receptionist')
        <script>
            window.patientLookup = function (searchUrl, bookUrl, patientsUrl) {
                return {
                    searchUrl,
                    bookUrl,
                    patientsUrl,
                    query: '',
                    results: [],
                    open: false,
                    searching: false,
                    searchError: '',
                    controller: null,
                    lookup() {
                        const term = this.query.trim();
                        if (this.controller) {
                            this.controller.abort();
                        }
                        if (term.length < 2) {
                            this.results = [];
                            this.open = false;
                            this.searching = false;
                            this.searchError = '';
                            this.controller = null;
                            return;
                        }
                        const controller = new AbortController();
                        this.controller = controller;
                        this.searching = true;
                        this.searchError = '';
                        this.open = true;
                        const timer = window.setTimeout(() => controller.abort(), 8000);
                        fetch(this.searchUrl + '?q=' + encodeURIComponent(term), {
                            headers: { Accept: 'application/json' },
                            signal: controller.signal,
                        }).then((response) => {
                            if (!response.ok) {
                                throw new Error('search failed');
                            }
                            return response.json();
                        }).then((payload) => {
                            if (this.controller !== controller) {
                                return;
                            }
                            this.results = payload.patients || [];
                            this.open = true;
                        }).catch((error) => {
                            if (error.name === 'AbortError') {
                                if (this.controller === controller) {
                                    this.searchError = 'Search is taking too long. Try again.';
                                    this.results = [];
                                }
                                return;
                            }
                            if (this.controller === controller) {
                                this.searchError = 'Search failed. Try again.';
                                this.results = [];
                            }
                        }).finally(() => {
                            window.clearTimeout(timer);
                            if (this.controller === controller) {
                                this.searching = false;
                                this.controller = null;
                            }
                        });
                    },
                    book(patient) {
                        const url = new URL(this.bookUrl, window.location.origin);
                        url.searchParams.set('patient_id', patient.id);
                        url.searchParams.set('q', patient.name);
                        window.location.assign(url.toString());
                    },
                    submit() {
                        if (this.results.length > 0) {
                            this.book(this.results[0]);
                            return;
                        }
                        const term = this.query.trim();
                        if (term.length >= 2) {
                            window.location.assign(this.patientsUrl + '?q=' + encodeURIComponent(term));
                        }
                    },
                    close() {
                        this.open = false;
                    },
                };
            };
        </script>
    @endif
    @stack('scripts')
</body>
</html>
