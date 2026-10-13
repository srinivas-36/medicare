{{--
    Dashboard menu.
    Links come from config/dashboard.php for the signed-in role.
    A link is shown only when that route exists. Otherwise it stays visible as "Soon".
--}}
@php
    $user = auth()->user();
    $role = $user->role ?? 'patient';
    $roleConfig = config("dashboard.roles.{$role}")
        ?? config('dashboard.roles.patient', [
            'label' => 'Patient',
            'links' => [],
        ]);
    $roleLabel = $roleConfig['label'];
    $links = $roleConfig['links'];
    $currentPath = '/'.ltrim(request()->path(), '/');
@endphp

<div
    x-show="navOpen"
    x-cloak
    class="fixed inset-0 z-40 bg-navy/40 lg:hidden"
    @click="navOpen = false"
></div>

<aside
    class="fixed inset-y-0 left-0 z-50 flex w-[280px] -translate-x-full flex-col border-r border-line bg-white transition-transform duration-200 lg:static lg:z-0 lg:!translate-x-0"
    :class="navOpen ? '!translate-x-0' : '-translate-x-full'"
>
    <div class="flex h-16 shrink-0 items-center justify-between border-b border-line px-5">
        <a href="{{ route('dashboard') }}" class="flex items-center no-underline">
            <img src="{{ asset('images/logo.png') }}" alt="Medicare" class="block h-8 w-auto">
        </a>
        <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-navy lg:hidden" @click="navOpen = false" aria-label="Close menu">
            <x-ui.icon name="close" class="h-5 w-5" />
        </button>
    </div>

    <div class="px-5 pt-5">
        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted">Signed in as</p>
        <p class="mt-1 text-sm font-semibold text-navy">{{ $roleLabel }}</p>
    </div>

    <nav class="mt-4 min-h-0 flex-1 overflow-y-auto px-3 pb-4" aria-label="{{ $roleLabel }} dashboard">
        <ul class="space-y-1">
            @foreach ($links as $link)
                @php
                    $routeName = $link['route'] ?? null;
                    $fallback = $link['fallback'] ?? null;
                    $hasRoute = $routeName && \Illuminate\Support\Facades\Route::has($routeName);
                    $href = $hasRoute ? route($routeName) : ($fallback ? url($fallback) : null);
                    $isActive = ($hasRoute && (request()->routeIs($routeName) || request()->routeIs($routeName.'.*')))
                        || ($fallback && rtrim($currentPath, '/') === rtrim($fallback, '/'));
                    $icon = $link['icon'] ?? 'grid';
                @endphp

                <li>
                    @if ($href)
                        <a
                            href="{{ $href }}"
                            @if ($isActive) aria-current="page" @endif
                            @click="navOpen = false"
                            @class([
                                'flex h-11 items-center gap-3 rounded-xl px-3 text-[15px] font-semibold transition',
                                'bg-primary text-white shadow-[0_8px_18px_rgba(23,105,255,0.22)]' => $isActive,
                                'text-text hover:bg-light hover:text-primary' => ! $isActive,
                            ])
                        >
                            <x-ui.icon :name="$icon" @class(['h-[18px] w-[18px]', 'text-white' => $isActive, 'text-primary' => ! $isActive]) />
                            {{ $link['label'] }}
                        </a>
                    @else
                        <span class="flex h-11 items-center justify-between rounded-xl px-3 text-[15px] font-semibold text-muted" title="This screen is not available yet">
                            <span class="inline-flex items-center gap-3">
                                <x-ui.icon :name="$icon" class="h-[18px] w-[18px] text-muted" />
                                {{ $link['label'] }}
                            </span>
                            <span class="badge-info">Soon</span>
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="shrink-0 border-t border-line p-4">
        <a href="{{ route('home') }}" class="flex h-10 items-center gap-2 rounded-xl px-3 text-sm font-semibold text-text no-underline hover:bg-light hover:text-primary">
            <x-ui.icon name="arrow" class="h-4 w-4 rotate-180" />
            Public site
        </a>
    </div>
</aside>
