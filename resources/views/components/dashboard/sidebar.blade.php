{{--
    Role navigation. Groups and links come from config/dashboard.php.
    A link is rendered only when its named route exists.
--}}
@php
    $user = auth()->user();
    $role = $user->role ?? 'patient';
    $roleConfig = config("dashboard.roles.{$role}")
        ?? config('dashboard.roles.patient', [
            'label' => 'Patient',
            'workspace' => 'Workspace',
            'groups' => [],
        ]);
    $roleLabel = $roleConfig['label'];
    $groups = $roleConfig['groups'] ?? [[
        'label' => null,
        'links' => $roleConfig['links'] ?? [],
    ]];

    $linkActive = function (array $link): bool {
        $names = $link['active'] ?? [($link['route'] ?? '')];
        $exact = (bool) ($link['exact'] ?? false);
        $active = false;

        foreach ($names as $name) {
            if ($name === '' || $name === null) {
                continue;
            }

            $active = $exact
                ? request()->routeIs($name)
                : (request()->routeIs($name) || request()->routeIs($name.'.*'));

            if ($active) {
                break;
            }
        }

        if (! $active || ! isset($link['query'])) {
            return $active;
        }

        foreach ($link['query'] as $key => $value) {
            $actual = request()->query($key, $link['missing'][$key] ?? null);

            if ((string) $actual !== (string) $value) {
                return false;
            }
        }

        return true;
    };
@endphp

<div
    x-show="navOpen"
    x-cloak
    class="fixed inset-0 z-40 bg-[#181A1E]/40 lg:hidden"
    @click="navOpen = false"
></div>

<aside
    class="dash-sidebar fixed inset-y-0 left-0 z-50 flex w-[248px] shrink-0 -translate-x-full flex-col overflow-hidden border-r border-[#E7EEF6] bg-white transition-[width,transform] duration-200 lg:static lg:z-0 lg:!translate-x-0"
    :class="navOpen ? '!translate-x-0' : '-translate-x-full'"
>
    <div class="sidebar-brand flex h-16 shrink-0 items-center gap-2 border-b border-[#E7EEF6] px-3">
        <a href="{{ route('home') }}" class="sidebar-logo flex min-w-0 flex-1 items-center no-underline">
            <img src="{{ asset('images/logo.png') }}" alt="Medicare" class="block h-7 w-auto">
        </a>
        <button
            type="button"
            class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-[#E7EEF6] bg-white text-[#64748B] transition hover:border-[#C5D9FF] hover:text-[#0067FF] lg:inline-flex"
            @click="toggleCollapsed()"
            :aria-expanded="collapsed ? 'false' : 'true'"
            :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
        >
            <span class="transition-transform duration-200" :class="collapsed ? 'rotate-180' : ''">
                <x-ui.icon name="chevron-left" class="h-4 w-4" />
            </span>
        </button>
        <button type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-[#181A1E] lg:hidden" @click="navOpen = false" aria-label="Close menu">
            <x-ui.icon name="close" class="h-5 w-5" />
        </button>
    </div>

    <nav class="dash-nav min-h-0 flex-1 overflow-y-auto px-3 py-3" aria-label="{{ $roleLabel }} workspace">
        @foreach ($groups as $group)
            <div class="{{ $loop->first ? 'mt-2' : 'mt-5' }}">
                @if (! empty($group['label']))
                    <p class="sidebar-heading px-3 pb-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-[#94A3B8]">{{ $group['label'] }}</p>
                @endif
                <ul class="space-y-0.5">
                    @foreach ($group['links'] as $link)
                        @php
                            $routeName = $link['route'] ?? null;
                            $hasRoute = $routeName && \Illuminate\Support\Facades\Route::has($routeName);
                            $href = $hasRoute ? route($routeName, $link['query'] ?? []) : null;
                            $isActive = $href && $linkActive($link);
                            $icon = $link['icon'] ?? 'grid';
                        @endphp
                        @if ($href)
                            <li>
                                <a
                                    href="{{ $href }}"
                                    @if ($isActive) aria-current="page" @endif
                                    @click="navOpen = false"
                                    title="{{ $link['label'] }}"
                                    @class([
                                        'sidebar-link flex h-10 items-center gap-2.5 rounded-xl px-3 text-[13px] transition',
                                        'bg-[#EAF2FF] font-semibold text-[#0067FF]' => $isActive,
                                        'font-medium text-[#181A1E] hover:bg-[#F8FAFC]' => ! $isActive,
                                    ])
                                >
                                    <x-ui.icon :name="$icon" @class(['h-4 w-4 shrink-0', 'text-[#0067FF]' => $isActive, 'text-[#64748B]' => ! $isActive]) />
                                    <span class="sidebar-label min-w-0 flex-1 truncate">{{ $link['label'] }}</span>
                                    @if (($link['route'] ?? '') === 'account.notifications' && ($unread ?? 0) > 0)
                                        <span class="sidebar-dot ml-auto inline-flex h-2 w-2 rounded-full bg-[#EF4444]" aria-label="{{ $unread }} unread"></span>
                                    @endif
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endforeach
        </nav>

    <div class="shrink-0 border-t border-[#E7EEF6] p-3">
        <!-- @if ($role === 'patient')
            <div class="sidebar-support rounded-2xl border border-[#E3EDF8] bg-[#F4F8FF] p-4">
                <a href="{{ route('contact') }}" title="Contact support" class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-[#0067FF] shadow-[0_1px_2px_rgba(15,23,42,0.06)] no-underline">
                    <x-ui.icon name="headset" class="h-5 w-5" />
                </a>
                <p class="sidebar-label mt-3 text-sm font-semibold text-[#181A1E]">Need help?</p>
                <p class="sidebar-label mt-1 text-xs leading-5 text-[#64748B]">Contact our support team for any assistance.</p>
                <a href="{{ route('contact') }}" class="sidebar-label mt-3 inline-flex h-9 w-full items-center justify-center rounded-lg border border-[#D7E3F4] bg-white text-sm font-semibold text-[#181A1E] no-underline transition hover:border-[#0067FF]/40">Contact Support</a>
            </div>
        @else
            <a href="{{ route('contact') }}" title="Contact support" class="sidebar-help flex items-center gap-3 rounded-2xl border border-[#D9E6FF] bg-[#F3F8FF] px-3 py-3 no-underline transition hover:border-[#0067FF]/30">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-[#0067FF] shadow-[0_1px_2px_rgba(15,23,42,0.06)]">
                    <x-ui.icon name="headset" class="h-4 w-4" />
                </span>
                <span class="sidebar-label min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-[#181A1E]">Need help?</span>
                    <span class="block text-xs text-[#64748B]">Contact support</span>
                </span>
                <x-ui.icon name="chevron-right" class="sidebar-help-chevron h-4 w-4 shrink-0 text-[#94A3B8]" />
            </a>
        @endif -->
        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button type="submit" title="{{ $role === 'patient' ? 'Logout' : 'Log out' }}" @class([
                'sidebar-link flex h-10 w-full items-center gap-2.5 rounded-xl px-3 text-[13px] font-semibold transition hover:bg-red-50',
                'text-[#DC2626]' => $role === 'patient',
                'text-[#B91C1C]' => $role !== 'patient',
            ])>
                <x-ui.icon name="logout" class="h-4 w-4 shrink-0" />
                <span class="sidebar-label min-w-0 flex-1 truncate text-left">{{ $role === 'patient' ? 'Logout' : 'Log out' }}</span>
            </button>
        </form>
    </div>
</aside>
