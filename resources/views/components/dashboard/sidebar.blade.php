@php
$user = auth()->user();

$role = $user->role ?? 'patient';
$name = $user->name ?? 'Account';
$email = $user->email ?? '';
$photo = $user->photo ?? null;

// Load navigation configuration for the current role.
$roleConfig = config("dashboard.roles.{$role}")
?? config('dashboard.roles.patient', [
'label' => 'Patient',
'links' => [],
]);

$roleLabel = $roleConfig['label'];
$links = $roleConfig['links'];

// Resolve profile photo.
$photoUrl = null;

if (is_string($photo) && $photo !== '') {
$photoUrl = str_starts_with($photo, 'http')
? $photo
: asset($photo);
}

// Generate initials when no photo is available.
$initials = collect(preg_split('/\s+/', trim($name)) ?: [])
->filter()
->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
->take(2)
->implode('');

$currentRoute = request()->route()?->getName();

$currentPath = '/' . ltrim(request()->path(), '/');
@endphp

<div class="lg:w-[280px] lg:shrink-0">

    {{-- Mobile menu toggle --}}
    <input
        id="dashboard-nav"
        type="checkbox"
        class="peer hidden">

    <label
        for="dashboard-nav"
        class="mb-4 flex h-12 cursor-pointer items-center justify-between rounded-xl border border-[#0066ff1f] bg-white px-4 text-sm font-semibold text-[#181A1E] shadow-[0_12px_40px_rgba(0,103,255,0.08)] lg:hidden">
        Menu

        <svg
            class="h-5 w-5 text-[#0067FF]"
            viewBox="0 0 24 24"
            fill="currentColor"
            aria-hidden="true">
            <path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z" />
        </svg>
    </label>

    <aside
        class="hidden rounded-2xl border border-[#0066ff1f] bg-white p-5 shadow-[0_12px_40px_rgba(0,103,255,0.08)] peer-checked:block lg:block">

        {{-- User profile --}}
        <div class="flex flex-col items-center border-b border-[#0066ff1f] pb-5 text-center">

            @if ($photoUrl)
            <img
                src="{{ $photoUrl }}"
                alt="{{ $name }}"
                class="h-20 w-20 rounded-full border-2 border-[#0067FF] object-cover">
            @else
            <span
                class="flex h-20 w-20 items-center justify-center rounded-full border-2 border-[#0067FF] bg-[#e8f2ff] text-xl font-bold text-[#0067FF]">
                {{ $initials !== '' ? $initials : 'M' }}
            </span>
            @endif

            <h2 class="mt-4 text-lg font-bold leading-7 text-[#181A1E]">
                {{ $name }}
            </h2>

            @if ($email !== '')
            <p class="mt-1 text-sm leading-6 text-[#4E545F]">
                {{ $email }}
            </p>
            @endif

            <span
                class="mt-3 inline-flex rounded-full bg-[#e8f2ff] px-3 py-1 text-xs font-semibold uppercase tracking-wide text-[#0067FF]">
                {{ $roleLabel }}
            </span>
        </div>

        {{-- Role-specific navigation --}}
        <nav class="mt-5" aria-label="{{ $roleLabel }} dashboard">
            <ul class="space-y-1.5">

                @foreach ($links as $link)
                @php
                $routeName = $link['route'] ?? null;
                $fallback = $link['fallback'] ?? null;

                $hasRoute = $routeName
                && \Illuminate\Support\Facades\Route::has($routeName);

                $href = $hasRoute
                ? route($routeName)
                : ($fallback ? url($fallback) : null);

                $isActive = (
                $hasRoute
                && request()->routeIs($routeName)
                ) || (
                $fallback
                && rtrim($currentPath, '/') === rtrim($fallback, '/')
                );
                @endphp

                <li>
                    @if ($href)
                    <a
                        href="{{ $href }}"
                        @if ($isActive) aria-current="page" @endif
                        @class([ 'flex h-11 items-center rounded-xl px-4 text-[15px] font-semibold leading-7 transition' , 'bg-[#0067FF] text-white'=> $isActive,
                        'text-[#4E545F] hover:bg-[#f7faff] hover:text-[#0067FF]' => !$isActive,
                        ])
                        >
                        {{ $link['label'] }}
                    </a>
                    @else
                    <span
                        class="flex h-11 items-center justify-between rounded-xl px-4 text-[15px] font-semibold leading-7 text-[#4E545F]/70"
                        title="This screen will be added in a later step">
                        {{ $link['label'] }}

                        <span class="rounded-full bg-[#f7faff] px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-[#0067FF]">
                            Soon
                        </span>
                    </span>
                    @endif
                </li>
                @endforeach

            </ul>
        </nav>

        {{-- Logout --}}
        <div class="mt-8">
            @if (\Illuminate\Support\Facades\Route::has('logout'))
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-[#181A1E] text-base font-semibold text-white hover:bg-[#2a2d33]">
                    Logout
                </button>
            </form>
            @else
            <a
                href="{{ route('login') }}"
                class="inline-flex h-12 w-full items-center justify-center rounded-xl bg-[#181A1E] text-base font-semibold text-white hover:bg-[#2a2d33]">
                Logout
            </a>
            @endif
        </div>

    </aside>
</div>