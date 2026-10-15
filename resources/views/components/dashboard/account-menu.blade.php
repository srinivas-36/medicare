@php
    $user = auth()->user();
    $role = $user->role ?? 'patient';
    $name = $user->name ?? 'Account';
    $photo = $user->photo ?? null;
    $roleLabel = $role === 'doctor'
        ? ($user->doctor?->specialization ?: 'Doctor')
        : config("dashboard.roles.{$role}.label", ucfirst($role));

    $photoUrl = null;

    if (is_string($photo) && $photo !== '') {
        $photoUrl = str_starts_with($photo, 'http') ? $photo : asset($photo);
    }

    $bareName = trim((string) preg_replace('/^dr\.?\s+/i', '', $name));
    $displayName = $role === 'doctor'
        ? (preg_match('/^dr\.?\s/i', $name) ? preg_replace('/^dr\.?\s*/i', 'Dr. ', $name) : 'Dr. '.$name)
        : $name;
    $initials = collect(preg_split('/\s+/', $bareName) ?: [])
        ->filter()
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');

    [$profileUrl, $settingsUrl] = match ($role) {
        'doctor' => [route('doctor.profile.show'), route('account.security')],
        'patient' => [route('patient.profile.show'), route('account.security')],
        'receptionist' => [route('receptionist.profile'), route('account.security')],
        default => [route('account.security'), route('account.security')],
    };
@endphp

<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button
        type="button"
        @class([
            'flex items-center gap-2.5 py-1 pl-1 text-left transition',
            'pr-1' => in_array($role, ['doctor', 'patient'], true),
            'rounded-full border border-line bg-white pr-3 hover:border-primary/40' => ! in_array($role, ['doctor', 'patient'], true),
        ])
        @click="open = !open"
        :aria-expanded="open ? 'true' : 'false'"
        aria-haspopup="menu"
    >
        @if ($photoUrl)
            <img src="{{ $photoUrl }}" alt="" class="h-9 w-9 rounded-full object-cover">
        @else
            <span @class([
                'flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold',
                'bg-[#7C3AED] text-white' => $role === 'doctor',
                'bg-light text-primary' => $role !== 'doctor',
            ])>
                {{ $initials !== '' ? $initials : 'M' }}
            </span>
        @endif
        <span class="hidden sm:block">
            <span class="block max-w-[140px] truncate text-sm font-semibold leading-5 text-[#181A1E]">{{ $displayName }}</span>
            <span class="block text-xs leading-4 text-[#64748B]">{{ $roleLabel }}</span>
        </span>
        @if ($role !== 'doctor')
            <x-ui.icon name="chevron" class="hidden h-4 w-4 text-muted sm:block" />
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        @click.outside="open = false"
        class="absolute right-0 z-50 mt-2 w-60 overflow-hidden rounded-2xl border border-line bg-white py-2 shadow-lift"
        role="menu"
    >
        <div class="border-b border-line px-4 py-3 sm:hidden">
            <p class="truncate text-sm font-semibold text-navy">{{ $name }}</p>
            <p class="truncate text-xs text-muted">{{ $user->email }}</p>
        </div>
        <a href="{{ $profileUrl }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-navy no-underline hover:bg-light" role="menuitem" @click="open = false">
            <x-ui.icon name="user" class="h-4 w-4 text-primary" />
            Profile
        </a>
        <a href="{{ route('account.notifications') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-navy no-underline hover:bg-light" role="menuitem" @click="open = false">
            <x-ui.icon name="bell" class="h-4 w-4 text-primary" />
            Notifications
        </a>
        <a href="{{ $settingsUrl }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-navy no-underline hover:bg-light" role="menuitem" @click="open = false">
            <x-ui.icon name="settings" class="h-4 w-4 text-primary" />
            Settings
        </a>
        <form method="POST" action="{{ route('logout') }}" class="border-t border-line">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm font-semibold text-danger hover:bg-red-50" role="menuitem">
                <x-ui.icon name="close" class="h-4 w-4" />
                Log out
            </button>
        </form>
    </div>
</div>
