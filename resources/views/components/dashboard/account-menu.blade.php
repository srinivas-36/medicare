@php
    $user = auth()->user();
    $role = $user->role ?? 'patient';
    $name = $user->name ?? 'Account';
    $photo = $user->photo ?? null;
    $roleLabel = config("dashboard.roles.{$role}.label", ucfirst($role));

    $photoUrl = null;

    if (is_string($photo) && $photo !== '') {
        $photoUrl = str_starts_with($photo, 'http') ? $photo : asset($photo);
    }

    $initials = collect(preg_split('/\s+/', trim($name)) ?: [])
        ->filter()
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');

    [$profileUrl, $settingsUrl] = match ($role) {
        'doctor' => [route('doctor.profile.show'), route('doctor.profile.edit')],
        'patient' => [route('patient.profile.show'), route('patient.profile.edit')],
        default => [route('profile.edit'), route('profile.edit')],
    };
@endphp

<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button
        type="button"
        class="flex items-center gap-3 rounded-full border border-line bg-white py-1 pl-1 pr-3 text-left transition hover:border-primary/40"
        @click="open = !open"
        :aria-expanded="open ? 'true' : 'false'"
        aria-haspopup="menu"
    >
        @if ($photoUrl)
            <img src="{{ $photoUrl }}" alt="" class="h-9 w-9 rounded-full object-cover">
        @else
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-light text-xs font-bold text-primary">
                {{ $initials !== '' ? $initials : 'M' }}
            </span>
        @endif
        <span class="hidden sm:block">
            <span class="block max-w-[140px] truncate text-sm font-semibold leading-5 text-navy">{{ $name }}</span>
            <span class="block text-xs leading-4 text-muted">{{ $roleLabel }}</span>
        </span>
        <x-ui.icon name="chevron" class="hidden h-4 w-4 text-muted sm:block" />
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
        <a href="{{ $settingsUrl }}" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-navy no-underline hover:bg-light" role="menuitem" @click="open = false">
            <x-ui.icon name="file" class="h-4 w-4 text-primary" />
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
